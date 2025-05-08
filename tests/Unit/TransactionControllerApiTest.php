<?php

namespace Tests\Unit;

use App\Http\Controllers\API\TransactionControllerApi;
use App\Http\Requests\TransferRequest;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionService;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mockery;

class TransactionControllerApiTest extends TestCase
{
    use RefreshDatabase;
    public function test_transfer_succeeds()
    {
        $user = User::factory()->make(['id' => 1, 'balance' => 1000]);
        Auth::shouldReceive('user')->andReturn($user);

        $transaction = Transaction::factory()->make([
            'payer_id' => $user->id,
            'payee_id' => 2,
            'amount' => 200,
            'type' => 'transfer',
            'status' => 'completed',
        ]);

        $request = Mockery::mock(TransferRequest::class);
        $request->shouldReceive('validated')->andReturn([
            'amount' => 200,
            'payee_id' => 2
        ]);

        $service = Mockery::mock(TransactionService::class);
        $service->shouldReceive('transfer')->andReturn($transaction);

        $controller = new TransactionControllerApi($service);
        $response = $controller->transfer($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Transferência realizada com sucesso.', $response->getData(true)['message']);
    }

    public function test_transfer_returns_403_for_insufficient_balance()
    {
        $user = User::factory()->make(['id' => 1, 'balance' => 50]);
        Auth::shouldReceive('user')->andReturn($user);

        $request = Mockery::mock(TransferRequest::class);
        $request->shouldReceive('validated')->andReturn([
            'amount' => 100,
            'payee_id' => 2
        ]);

        $service = Mockery::mock(TransactionService::class);
        $service->shouldReceive('transfer')
            ->andThrow(new \Exception('Saldo insuficiente para a transferência.', 403));

        $controller = new TransactionControllerApi($service);
        $response = $controller->transfer($request);

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals('Saldo insuficiente para a transferência.', $response->getData(true)['message']);
    }

    public function test_transfer_returns_422_for_invalid_payee_or_self_transfer()
    {
        $user = User::factory()->make(['id' => 1, 'balance' => 1000]);
        Auth::shouldReceive('user')->andReturn($user);

        $request = Mockery::mock(TransferRequest::class);
        $request->shouldReceive('validated')->andReturn([
            'amount' => 100,
            'payee_id' => 1
        ]);

        $service = Mockery::mock(TransactionService::class);
        $service->shouldReceive('transfer')
            ->andThrow(new \Exception('Destinatário inválido.', 422));

        $controller = new TransactionControllerApi($service);
        $response = $controller->transfer($request);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals('Destinatário inválido.', $response->getData(true)['message']);
    }

    public function test_transfer_returns_500_on_generic_error()
    {
        $user = User::factory()->make(['id' => 1, 'balance' => 1000]);
        Auth::shouldReceive('user')->andReturn($user);

        $request = Mockery::mock(TransferRequest::class);
        $request->shouldReceive('validated')->andReturn([
            'amount' => 100,
            'payee_id' => 2
        ]);

        $service = Mockery::mock(TransactionService::class);
        $service->shouldReceive('transfer')
            ->andThrow(new \Exception('Erro inesperado'));

        $controller = new TransactionControllerApi($service);
        $response = $controller->transfer($request);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('Erro inesperado', $response->getData(true)['message']);
    }

    public function test_deposit_succeeds()
    {
        $user = User::factory()->make(['id' => 1, 'balance' => 100]);
        Auth::shouldReceive('user')->andReturn($user);

        $transaction = Transaction::factory()->make([
            'payer_id' => null,
            'payee_id' => $user->id,
            'amount' => 200,
            'type' => 'deposit',
            'status' => 'completed',
        ]);

        $request = Mockery::mock(\App\Http\Requests\DepositRequest::class);
        $request->shouldReceive('validated')->andReturn(['amount' => 200]);

        $service = Mockery::mock(TransactionService::class);
        $service->shouldReceive('deposit')->andReturn($transaction);

        $controller = new \App\Http\Controllers\API\TransactionControllerApi($service);
        $response = $controller->deposit($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Depósito realizado com sucesso.', $response->getData(true)['message']);
    }

    public function test_deposit_returns_403_on_business_exception()
    {
        $user = User::factory()->make(['id' => 1]);
        Auth::shouldReceive('user')->andReturn($user);

        $request = Mockery::mock(\App\Http\Requests\DepositRequest::class);
        $request->shouldReceive('validated')->andReturn(['amount' => 100]);

        $service = Mockery::mock(TransactionService::class);
        $service->shouldReceive('deposit')
            ->andThrow(new \Exception('Depósito não permitido.', 403));

        $controller = new \App\Http\Controllers\API\TransactionControllerApi($service);
        $response = $controller->deposit($request);

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals('Erro ao realizar o depósito: Depósito não permitido.', $response->getData(true)['message']);
    }

    public function test_deposit_returns_500_on_generic_error()
    {
        $user = User::factory()->make(['id' => 1]);
        Auth::shouldReceive('user')->andReturn($user);

        $request = Mockery::mock(\App\Http\Requests\DepositRequest::class);
        $request->shouldReceive('validated')->andReturn(['amount' => 100]);

        $service = Mockery::mock(TransactionService::class);
        $service->shouldReceive('deposit')
            ->andThrow(new \Exception('Erro inesperado'));

        $controller = new \App\Http\Controllers\API\TransactionControllerApi($service);
        $response = $controller->deposit($request);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('Erro ao realizar o depósito: Erro inesperado', $response->getData(true)['message']);
    }
    public function test_reverse_succeeds()
    {
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('input')->with('reason')->andReturn('Erro no valor');

        $transactionMock = ['transfer', 800.00]; // retorno da service

        $service = Mockery::mock(TransactionService::class);
        $service->shouldReceive('reverse')->with(10, 'Erro no valor')->andReturn($transactionMock);

        $controller = new TransactionControllerApi($service);
        $response = $controller->reverse(10, $request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Transação revertida com sucesso.', $response->getData(true)['message']);
        $this->assertEquals($transactionMock, $response->getData(true)['transaction']);
    }

    public function test_reverse_fails_with_transaction_not_found()
    {
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('input')->with('reason')->andReturn(null);

        $service = Mockery::mock(TransactionService::class);
        $service->shouldReceive('reverse')->andThrow(new \Exception('Transação não encontrada ou não pode ser revertida.', 400));

        $controller = new TransactionControllerApi($service);
        $response = $controller->reverse(10, $request);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('Transação não encontrada ou não pode ser revertida.', $response->getData(true)['message']);
    }

    public function test_reverse_fails_with_unauthorized_user()
    {
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('input')->with('reason')->andReturn('Teste');

        $service = Mockery::mock(TransactionService::class);
        $service->shouldReceive('reverse')->andThrow(new \Exception('Você não tem permissão para reverter esta transação.', 403));

        $controller = new TransactionControllerApi($service);
        $response = $controller->reverse(1, $request);

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals('Você não tem permissão para reverter esta transação.', $response->getData(true)['message']);
    }

    public function test_reverse_fails_with_invalid_transaction_type()
    {
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('input')->with('reason')->andReturn(null);

        $service = Mockery::mock(TransactionService::class);
        $service->shouldReceive('reverse')->andThrow(new \Exception('Tipo de transação inválido para reversão.', 422));

        $controller = new TransactionControllerApi($service);
        $response = $controller->reverse(5, $request);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals('Tipo de transação inválido para reversão.', $response->getData(true)['message']);
    }

    public function test_reverse_returns_500_on_generic_exception()
    {
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('input')->with('reason')->andReturn(null);

        $service = Mockery::mock(TransactionService::class);
        $service->shouldReceive('reverse')->andThrow(new \Exception('Falha inesperada'));

        $controller = new TransactionControllerApi($service);
        $response = $controller->reverse(99, $request);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('Falha inesperada', $response->getData(true)['message']);
    }

    public function test_statement_succeeds()
    {
        $transactions = [
            ['id' => 1, 'type' => 'deposit', 'amount' => 100],
            ['id' => 2, 'type' => 'transfer', 'amount' => 50]
        ];

        $expected = [
            'transactions' => $transactions,
            'balance' => 500.00
        ];

        $service = Mockery::mock(TransactionService::class);
        $service->shouldReceive('getUserStatement')->once()->andReturn($expected);

        $controller = new TransactionControllerApi($service);
        $response = $controller->statement();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Extrato consultado com sucesso.', $response->getData(true)['message']);
        $this->assertEquals($expected['transactions'], $response->getData(true)['transactions']);
        $this->assertEquals($expected['balance'], $response->getData(true)['balance']);
    }

    public function test_statement_returns_404_when_no_transactions_found()
    {
        $service = Mockery::mock(TransactionService::class);
        $service->shouldReceive('getUserStatement')
            ->andThrow(new \Exception('Você não tem transações registradas.', 404));

        $controller = new TransactionControllerApi($service);
        $response = $controller->statement();

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('Você não tem transações registradas.', $response->getData(true)['message']);
    }

    public function test_statement_returns_500_on_generic_error()
    {
        $service = Mockery::mock(TransactionService::class);
        $service->shouldReceive('getUserStatement')
            ->andThrow(new \Exception('Erro inesperado'));

        $controller = new TransactionControllerApi($service);
        $response = $controller->statement();

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('Erro inesperado', $response->getData(true)['message']);
    }
}
