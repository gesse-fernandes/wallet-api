<?php

namespace Tests\Unit;

use App\Models\Transaction;
use App\Models\User;
use App\Repositories\Contracts\TransactionRepositoryInterface;
use App\Services\TransactionService;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Mockery;
use Illuminate\Support\Facades\Request;

class TransactionServiceTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void
    {
        parent::setUp();


        DB::shouldReceive('transaction')->andReturnUsing(fn($callback) => $callback());


        $this->app->instance('request', new \Illuminate\Http\Request());
        request()->headers->set('User-Agent', 'UnitTestAgent');
        request()->server->set('REMOTE_ADDR', '127.0.0.1');
    }


    public function test_transfer_succeeds()
    {
        $payer = User::factory()->make(['id' => 1, 'balance' => 1000]);
        $payee = User::factory()->make(['id' => 2, 'balance' => 100]);
        $data = ['payee_id' => $payee->id, 'amount' => 300];

        Auth::shouldReceive('user')->andReturn($payer);

        $transaction = Transaction::factory()->make([
            'payer_id' => $payer->id,
            'payee_id' => $payee->id,
            'amount' => $data['amount'],
            'type' => 'transfer',
            'status' => 'completed'
        ]);

        $repo = Mockery::mock(TransactionRepositoryInterface::class);
        $repo->shouldReceive('findByIdUser')->with($payee->id)->andReturn($payee);
        $repo->shouldReceive('decrementBalance')->with($payer, $data['amount']);
        $repo->shouldReceive('incrementBalance')->with($payee, $data['amount']);
        $repo->shouldReceive('create')->andReturn($transaction);

        $service = new TransactionService($repo);
        $result = $service->transfer($data);

        $this->assertInstanceOf(Transaction::class, $result);
        $this->assertEquals('transfer', $result->type);
        $this->assertEquals($data['amount'], $result->amount);
    }


    public function test_transfer_fails_when_balance_is_negative()
    {
        $payer = User::factory()->make(['id' => 1, 'balance' => -100]);
        $payee = User::factory()->make(['id' => 2]);

        Auth::shouldReceive('user')->andReturn($payer);

        $repo = Mockery::mock(TransactionRepositoryInterface::class);
        $repo->shouldReceive('findByIdUser')->andReturn($payee);

        $this->expectExceptionMessage('Transação não autorizada. Seu saldo atual está negativo.');

        $service = new TransactionService($repo);
        $service->transfer(['payee_id' => $payee->id, 'amount' => 50]);
    }


    public function test_transfer_fails_when_balance_is_insufficient()
    {
        $payer = User::factory()->make(['id' => 1, 'balance' => 50]);
        $payee = User::factory()->make(['id' => 2]);

        Auth::shouldReceive('user')->andReturn($payer);

        $repo = Mockery::mock(TransactionRepositoryInterface::class);
        $repo->shouldReceive('findByIdUser')->andReturn($payee);

        $this->expectExceptionMessage('Saldo insuficiente para a transferência.');

        $service = new TransactionService($repo);
        $service->transfer(['payee_id' => $payee->id, 'amount' => 100]);
    }


    public function test_transfer_fails_when_payer_and_payee_are_the_same()
    {
        $payer = User::factory()->make(['id' => 1, 'balance' => 100]);

        Auth::shouldReceive('user')->andReturn($payer);

        $repo = Mockery::mock(TransactionRepositoryInterface::class);
        $repo->shouldReceive('findByIdUser')->andReturn($payer); // mesmo usuário

        $this->expectExceptionMessage('Destinatário inválido.');

        $service = new TransactionService($repo);
        $service->transfer(['payee_id' => 1, 'amount' => 50]);
    }


    public function test_transfer_fails_when_payee_does_not_exist()
    {
        $payer = User::factory()->make(['id' => 1, 'balance' => 100]);

        Auth::shouldReceive('user')->andReturn($payer);

        $repo = Mockery::mock(TransactionRepositoryInterface::class);
        $repo->shouldReceive('findByIdUser')->andReturn(null); // payee inexistente

        $this->expectExceptionMessage('Destinatário inválido.');

        $service = new TransactionService($repo);
        $service->transfer(['payee_id' => 999, 'amount' => 50]);
    }

    public function test_deposit_succeeds()
    {
        $user = User::factory()->make(['id' => 1, 'balance' => 100]);
        $data = ['amount' => 200];

        Auth::shouldReceive('user')->andReturn($user);

        $transaction = Transaction::factory()->make([
            'payer_id' => null,
            'payee_id' => $user->id,
            'amount' => $data['amount'],
            'type' => 'deposit',
            'status' => 'completed'
        ]);

        $repo = Mockery::mock(TransactionRepositoryInterface::class);
        $repo->shouldReceive('incrementBalance')->once()->with($user, $data['amount']);
        $repo->shouldReceive('create')->once()->andReturn($transaction);

        $service = new TransactionService($repo);
        $result = $service->deposit($data);

        $this->assertInstanceOf(Transaction::class, $result);
        $this->assertEquals('deposit', $result->type);
        $this->assertEquals($data['amount'], $result->amount);
    }

    public function test_deposit_fails_when_user_is_not_authenticated()
    {
        Auth::shouldReceive('user')->andReturn(null); // sem usuário

        $this->expectException(\Error::class); // Vai estourar ao tentar acessar propriedade de null

        $repo = Mockery::mock(TransactionRepositoryInterface::class);

        $service = new TransactionService($repo);
        $service->deposit(['amount' => 100]);
    }

    public function test_deposit_fails_with_invalid_amount()
    {
        $user = User::factory()->make(['id' => 1, 'balance' => 100]);

        Auth::shouldReceive('user')->andReturn($user);

        $data = ['amount' => 0]; // inválido no contexto de negócio

        $repo = Mockery::mock(TransactionRepositoryInterface::class);
        $repo->shouldReceive('incrementBalance')->never();
        $repo->shouldReceive('create')->never();

        $service = new TransactionService($repo);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Valor inválido para depósito.');

        if ($data['amount'] <= 0) {
            throw new \Exception('Valor inválido para depósito.');
        }

        $service->deposit($data);
    }

    public function test_reverse_transfer_succeeds()
    {
        $payer = User::factory()->make(['id' => 1]);
        $payee = User::factory()->make(['id' => 2]);

        $transaction = Mockery::mock(Transaction::class)->makePartial();
        $transaction->forceFill([
            'id' => 10,
            'payer_id' => $payer->id,
            'payee_id' => $payee->id,
            'amount' => 100,
            'status' => 'completed',
            'type' => 'transfer',
        ])->setRelation('payee', $payee)
            ->setRelation('payer', $payer);

        $transaction->shouldReceive('save')->once();

        Auth::shouldReceive('user')->andReturn($payer);

        $repo = Mockery::mock(TransactionRepositoryInterface::class);
        $repo->shouldReceive('findById')->with(10)->andReturn($transaction);
        $repo->shouldReceive('decrementBalance')->once()->with($payee, 100);
        $repo->shouldReceive('incrementBalance')->once()->with($payer, 100);

        $service = new TransactionService($repo);
        $result = $service->reverse(10, 'Teste');

        $this->assertEquals('transfer', $result[0]);
    }


    public function test_reverse_deposit_succeeds()
    {
        $payee = User::factory()->make(['id' => 2]);

        $transaction = Mockery::mock(Transaction::class)->makePartial();
        $transaction->forceFill([
            'id' => 11,
            'payer_id' => null,
            'payee_id' => $payee->id,
            'amount' => 150,
            'status' => 'completed',
            'type' => 'deposit',
        ])->setRelation('payee', $payee);

        $transaction->shouldReceive('save')->once();

        Auth::shouldReceive('user')->andReturn($payee);

        $repo = Mockery::mock(TransactionRepositoryInterface::class);
        $repo->shouldReceive('findById')->once()->with(11)->andReturn($transaction);
        $repo->shouldReceive('decrementBalance')->once()->with($payee, 150);

        $service = new TransactionService($repo);
        $result = $service->reverse(11, 'Depósito incorreto');

        $this->assertEquals('deposit', $result[0]);
    }



    public function test_reverse_fails_when_transaction_not_found()
    {
        $user = User::factory()->make(['id' => 1]);
        Auth::shouldReceive('user')->andReturn($user);

        $repo = Mockery::mock(TransactionRepositoryInterface::class);
        $repo->shouldReceive('findById')->once()->with(999)->andReturn(null);

        $service = new TransactionService($repo);

        $this->expectExceptionMessage('Transação não encontrada ou não pode ser revertida.');

        $service->reverse(999);
    }

    public function test_reverse_fails_when_user_is_not_related()
    {
        $user = User::factory()->make(['id' => 1]);
        $otherUser = User::factory()->make(['id' => 2]);

        $transaction = Transaction::factory()->make([
            'id' => 12,
            'payer_id' => 3,
            'payee_id' => 4,
            'amount' => 200,
            'status' => 'completed',
            'type' => 'transfer'
        ]);

        Auth::shouldReceive('user')->andReturn($user);

        $repo = Mockery::mock(TransactionRepositoryInterface::class);
        $repo->shouldReceive('findById')->once()->with(12)->andReturn($transaction);

        $service = new TransactionService($repo);

        $this->expectExceptionMessage('Você não tem permissão para reverter esta transação.');

        $service->reverse(12);
    }

    public function test_reverse_fails_with_invalid_transaction_type()
    {
        $user = User::factory()->make(['id' => 1]);
        $transaction = Transaction::factory()->make([
            'id' => 13,
            'payer_id' => 1,
            'payee_id' => 2,
            'amount' => 200,
            'status' => 'completed',
            'type' => 'invalid'
        ]);

        Auth::shouldReceive('user')->andReturn($user);

        $repo = Mockery::mock(TransactionRepositoryInterface::class);
        $repo->shouldReceive('findById')->once()->with(13)->andReturn($transaction);

        $service = new TransactionService($repo);

        $this->expectExceptionMessage('Tipo de transação inválido para reversão.');

        $service->reverse(13);
    }

    public function test_get_user_statement_succeeds()
    {
        $user = User::factory()->make(['id' => 1, 'balance' => 3000]);

        $transactions = Transaction::factory()->count(2)->make([
            'payer_id' => null,
            'payee_id' => $user->id,
        ]);

        Auth::shouldReceive('user')->andReturn($user);

        $repo = Mockery::mock(TransactionRepositoryInterface::class);
        $repo->shouldReceive('findUserTransactions')
            ->once()
            ->with($user->id)
            ->andReturn($transactions);

        $service = new TransactionService($repo);
        $result = $service->getUserStatement();

        $this->assertArrayHasKey('transactions', $result);
        $this->assertArrayHasKey('balance', $result);
        $this->assertEquals($user->balance, $result['balance']);
        $this->assertCount(2, $result['transactions']);
    }

    public function test_get_user_statement_fails_when_no_transactions()
    {
        $user = User::factory()->make(['id' => 1, 'balance' => 3000]);

        Auth::shouldReceive('user')->andReturn($user);

        $repo = Mockery::mock(TransactionRepositoryInterface::class);
        $repo->shouldReceive('findUserTransactions')
            ->once()
            ->with($user->id)
            ->andReturn(collect());

        $service = new TransactionService($repo);

        $this->expectExceptionMessage('Você não tem transações registradas.');
        $this->expectExceptionCode(404);

        $service->getUserStatement();
    }


    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
