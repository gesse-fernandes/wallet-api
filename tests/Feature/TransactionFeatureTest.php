<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class TransactionFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_transfer_succeeds()
    {
        $payer = User::factory()->create(['balance' => 1000]);
        $payee = User::factory()->create();

        $token = JWTAuth::fromUser($payer);

        $payload = [
            'amount' => 200,
            'payee_id' => $payee->id,
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/transactions/transfer', $payload);

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Transferência realizada com sucesso.'])
            ->assertJsonStructure(['transaction', 'balance']);
    }

    public function test_transfer_fails_due_to_insufficient_balance()
    {
        $payer = User::factory()->create(['balance' => 50]);
        $payee = User::factory()->create();

        $token = JWTAuth::fromUser($payer);

        $payload = [
            'amount' => 100,
            'payee_id' => $payee->id,
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/transactions/transfer', $payload);

        $response->assertStatus(403)
            ->assertJsonFragment(['message' => 'Saldo insuficiente para a transferência.']);
    }

    public function test_transfer_fails_when_transferring_to_self()
    {
        $user = User::factory()->create(['balance' => 500]);

        $token = JWTAuth::fromUser($user);

        $payload = [
            'amount' => 100,
            'payee_id' => $user->id,
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/transactions/transfer', $payload);

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'Destinatário inválido.']);
    }

    public function test_transfer_fails_when_payee_does_not_exist()
    {
        $user = User::factory()->create(['balance' => 1000]);

        $token = JWTAuth::fromUser($user);

        $payload = [
            'amount' => 100,
            'payee_id' => 99999,
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/transactions/transfer', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['payee_id']);
    }

    public function test_transfer_fails_with_invalid_payload()
    {
        $user = User::factory()->create(['balance' => 1000]);

        $token = JWTAuth::fromUser($user);

        $payload = [
            'amount' => 0,
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/transactions/transfer', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount', 'payee_id']);
    }

    public function test_transfer_requires_authentication()
    {
        $payload = [
            'amount' => 100,
            'payee_id' => 1,
        ];

        $response = $this->postJson('/api/transactions/transfer', $payload);

        $response->assertStatus(401);
    }

    public function test_deposit_succeeds()
    {
        $user = User::factory()->create(['balance' => 100]);

        $token = \Tymon\JWTAuth\Facades\JWTAuth::fromUser($user);

        $payload = ['amount' => 250];

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/transactions/deposit', $payload);

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Depósito realizado com sucesso.'])
            ->assertJsonStructure(['transaction', 'balance']);
    }

    public function test_deposit_fails_with_invalid_amount()
    {
        $user = User::factory()->create();

        $token = \Tymon\JWTAuth\Facades\JWTAuth::fromUser($user);

        $payload = ['amount' => 0]; // inválido

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/transactions/deposit', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    public function test_deposit_requires_authentication()
    {
        $payload = ['amount' => 100];

        $response = $this->postJson('/api/transactions/deposit', $payload);

        $response->assertStatus(401);
    }

    public function test_deposit_returns_500_on_exception()
    {
        $user = User::factory()->create();

        $mock = \Mockery::mock(\App\Services\TransactionService::class);
        $mock->shouldReceive('deposit')
            ->andThrow(new \Exception('Falha no serviço'));


        $this->app->instance(\App\Services\TransactionService::class, $mock);

        $token = \Tymon\JWTAuth\Facades\JWTAuth::fromUser($user);

        $payload = ['amount' => 100];

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/transactions/deposit', $payload);

        $response->assertStatus(500)
            ->assertJsonFragment(['message' => 'Erro ao realizar o depósito: Falha no serviço']);
    }
    public function test_reverse_transfer_succeeds()
    {
        $payer = User::factory()->create(['balance' => 500]);
        $payee = User::factory()->create(['balance' => 300]);


        $transaction = Transaction::factory()->create([
            'payer_id' => $payer->id,
            'payee_id' => $payee->id,
            'amount' => 100,
            'type' => 'transfer',
            'status' => 'completed',
        ]);

        $token = JWTAuth::fromUser($payer);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/transactions/reverse/{$transaction->id}", ['reason' => 'Teste de reversão']);

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Transação revertida com sucesso.']);
    }

    public function test_reverse_fails_if_transaction_not_found()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/transactions/reverse/999999', ['reason' => 'Inexistente']);

        $response->assertStatus(400)
            ->assertJsonFragment(['message' => 'Transação não encontrada ou não pode ser revertida.']);
    }

    public function test_reverse_fails_if_user_not_authorized()
    {
        $payer = User::factory()->create();
        $otherUser = User::factory()->create();

        $transaction = Transaction::factory()->create([
            'payer_id' => $payer->id,
            'payee_id' => $payer->id,
            'amount' => 100,
            'type' => 'transfer',
            'status' => 'completed',
        ]);

        $token = JWTAuth::fromUser($otherUser);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/transactions/reverse/{$transaction->id}", ['reason' => 'Inválido']);

        $response->assertStatus(403)
            ->assertJsonFragment(['message' => 'Você não tem permissão para reverter esta transação.']);
    }

    public function test_reverse_requires_authentication()
    {
        $transaction = Transaction::factory()->create();

        $response = $this->postJson("/api/transactions/reverse/{$transaction->id}");

        $response->assertStatus(401);
    }

    public function test_statement_succeeds_with_transactions()
    {
        $user = User::factory()->create(['balance' => 500]);

        Transaction::factory()->count(2)->create([
            'payee_id' => $user->id,
            'status' => 'completed',
        ]);

        $token = \Tymon\JWTAuth\Facades\JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/transactions/statement');

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Extrato consultado com sucesso.'])
            ->assertJsonStructure(['transactions', 'balance']);
    }

    public function test_statement_fails_with_no_transactions()
    {
        $user = User::factory()->create();

        $token = \Tymon\JWTAuth\Facades\JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/transactions/statement');

        $response->assertStatus(404)
            ->assertJsonFragment(['message' => 'Você não tem transações registradas.']);
    }

    public function test_statement_requires_authentication()
    {
        $response = $this->getJson('/api/transactions/statement');

        $response->assertStatus(401);
    }
}
