<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class RegisterFeatureTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    use RefreshDatabase;

    public function test_user_can_register_successfully()
    {
        $user = User::factory()->make();
        $address = Address::factory()->make();

        $payload = array_merge($user->only(['name', 'email', 'cpf_cnpj']), [
            'password' => 'Senha123!',
            'password_confirmation' => 'Senha123!',
            'street' => $address->street,
            'number' => $address->number,
            'neighborhood' => $address->neighborhood,
            'city' => $address->city,
            'state' => $address->state,
            'zipcode' => $address->zipcode,
        ]);

        $response = $this->postJson('/api/auth/register', $payload);

        $response->assertStatus(200)
            ->assertJsonStructure(['id', 'name', 'token']);

        $this->assertDatabaseHas('users', ['email' => $user->email]);
    }

    public function test_register_validation_fails_with_empty_payload()
    {
        $response = $this->postJson('/api/auth/register', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'name',
                'email',
                'cpf_cnpj',
                'password',
                'street',
                'number',
                'neighborhood',
                'city',
                'state',
                'zipcode',
            ]);
    }
    public function test_register_fails_if_email_already_exists()
    {
        $existingUser = User::factory()->create(['email' => 'ja@existe.com']);

        $address = Address::factory()->make();

        $newUser = User::factory()->make([
            'email' => $existingUser->email,
        ]);

        $payload = array_merge($newUser->only(['name', 'email', 'cpf_cnpj']), [
            'password' => 'Senha123!',
            'password_confirmation' => 'Senha123!',
            'street' => $address->street,
            'number' => $address->number,
            'neighborhood' => $address->neighborhood,
            'city' => $address->city,
            'state' => $address->state,
            'zipcode' => $address->zipcode,
        ]);

        $response = $this->postJson('/api/auth/register', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_register_returns_500_on_service_exception()
    {

        $this->mock(\App\Services\AuthService::class, function ($mock) {
            $mock->shouldReceive('register')
                ->once()
                ->andThrow(new \Exception('Erro interno'));
        });

        $address = Address::factory()->make();
        $user = User::factory()->make(['address_id' => null]);

        $payload = [
            'name' => $user->name,
            'email' => $user->email,
            'cpf_cnpj' => $user->cpf_cnpj,
            'password' => 'Senha123!',
            'password_confirmation' => 'Senha123!',
            'street' => $address->street,
            'number' => $address->number,
            'neighborhood' => $address->neighborhood,
            'city' => $address->city,
            'state' => $address->state,
            'zipcode' => $address->zipcode,
        ];

        $response = $this->postJson('/api/auth/register', $payload);

        $response->assertStatus(500);
        $response->assertJson([
            'message' => 'Erro interno',
        ]);
    }
}
