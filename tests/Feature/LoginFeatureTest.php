<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginFeatureTest extends TestCase
{
    use RefreshDatabase;
    public function test_user_can_login_and_receive_token()
    {
        $password = 'Senha123!';
        $user = User::factory()->create([
            'email' => 'joao@email.com',
            'password' => Hash::make($password),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => $password,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['id', 'name', 'token']);
    }

    public function test_login_fails_with_wrong_password()
    {
        $user = User::factory()->create([
            'email' => 'joao@email.com',
            'password' => Hash::make('SenhaCorreta123!'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'SenhaErrada!',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Credenciais inválidas.',
            ]);
    }

    public function test_login_fails_with_invalid_payload()
    {
        $response = $this->postJson('/api/auth/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_login_returns_500_on_service_exception()
    {
        $this->mock(\App\Services\AuthService::class, function ($mock) {
            $mock->shouldReceive('login')
                ->once()
                ->andThrow(new \Exception('Erro interno'));
        });

        $user = User::factory()->make();

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'Senha123!',
        ]);

        $response->assertStatus(500)
            ->assertJson([
                'message' => 'Erro interno',
            ]);
    }
}
