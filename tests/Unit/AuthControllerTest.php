<?php

namespace Tests\Unit;

use App\Http\Controllers\API\AuthControllerApi;
use App\Http\Requests\LoginRequest;
use Tests\TestCase;
use App\Models\User;
use App\Models\Address;
use App\Http\Requests\RegisterRequest;
use App\Repositories\Contracts\AddressRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Http\JsonResponse;
use App\Services\AuthService;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Mockery;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;
    public function test_register_returns_token_and_user_data_on_success()
    {
        $address = Address::factory()->make();
        $user = User::factory()->make(['address_id' => null]);

        $data = [
            'name' => $user->name,
            'email' => $user->email,
            'cpf_cnpj' => $user->cpf_cnpj,
            'password' => 'Senha123!',
            'street' => $address->street,
            'number' => $address->number,
            'neighborhood' => $address->neighborhood,
            'city' => $address->city,
            'state' => $address->state,
            'zipcode' => $address->zipcode,
        ];

        $request = Mockery::mock(\App\Http\Requests\RegisterRequest::class);
        $request->shouldReceive('validated')->andReturn($data);

        $authService = Mockery::mock(\App\Services\AuthService::class);
        $authService->shouldReceive('register')->with($data)->andReturn((function () use ($user) {
            $user->id = 1;
            return $user;
        })());

        JWTAuth::shouldReceive('fromUser')
            ->once()
            ->with($user)
            ->andReturn('fake-jwt-token');

        $controller = new AuthControllerApi($authService);
        $response = $controller->register($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->status());

        $json = $response->getData(true);
        $this->assertEquals(1, $json['id']);
        $this->assertEquals($user->name, $json['name']);
        $this->assertEquals('Bearer fake-jwt-token', $json['token']);
    }

    public function test_register_returns_500_when_auth_service_fails()
    {
        $data = [
            'name' => 'Erro Teste',
            'email' => 'erro@email.com',
            'cpf_cnpj' => '12345678900',
            'password' => 'Senha123!',
            'street' => 'Rua A',
            'number' => '10',
            'neighborhood' => 'Bairro X',
            'city' => 'Cidade',
            'state' => 'SP',
            'zipcode' => '12345-000',
        ];

        // Mock do RegisterRequest com Mockery
        $request = \Mockery::mock(\App\Http\Requests\RegisterRequest::class);
        $request->shouldReceive('validated')->andReturn($data);

        // Mock do AuthService lançando exceção
        $authService = \Mockery::mock(\App\Services\AuthService::class);
        $authService->shouldReceive('register')
            ->once()
            ->with($data)
            ->andThrow(new \Exception('Erro interno'));

        // Executa o controller
        $controller = new  AuthControllerApi($authService);
        $response = $controller->register($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(500, $response->status());

        $json = $response->getData(true);
        $this->assertEquals('Erro interno', $json['message']);
    }

    public function test_login_returns_token_and_user_data_on_success()
    {
        $user = User::factory()->create(['id' => 1]);

        $data = [
            'email' => $user->email,
            'password' => 'Senha123!',
        ];

        $request = Mockery::mock(LoginRequest::class);
        $request->shouldReceive('validated')->andReturn($data);

        $token = 'Bearer ' . JWTAuth::fromUser($user);

        $authService = \Mockery::mock(\App\Services\AuthService::class);
        $authService->shouldReceive('login')->with($data)->andReturn([
            'user' => $user,
            'token' => $token,
        ]);

        $controller = new AuthControllerApi($authService);
        $response = $controller->login($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->status());

        $json = $response->getData(true);
        $this->assertEquals(1, $json['id']);
        $this->assertEquals($user->name, $json['name']);
        $this->assertStringStartsWith('Bearer ', $json['token']);
    }


    public function test_login_returns_401_when_service_throws_exception()
    {
        $data = [
            'email' => 'erro@email.com',
            'password' => 'senhaerrada',
        ];

        $request = Mockery::mock(LoginRequest::class);
        $request->shouldReceive('validated')->andReturn($data);

        $authService = Mockery::mock(AuthService::class);
        $authService->shouldReceive('login')
            ->with($data)
            ->andThrow(new \Exception('Credenciais inválidas.'));

        $controller = new AuthControllerApi($authService);
        $response = $controller->login($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(401, $response->status());

        $json = $response->getData(true);
        $this->assertEquals('Credenciais inválidas.', $json['message']);
    }

    public function test_logout_returns_success_message()
    {
        $authService = Mockery::mock(AuthService::class);
        $authService->shouldReceive('logout')->once()->andReturnNull();

        $controller = new AuthControllerApi($authService);
        $response = $controller->logout();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->status());

        $json = $response->getData(true);
        $this->assertEquals('Logout realizado com sucesso.', $json['message']);
    }

    public function test_logout_returns_500_on_failure()
    {
        $authService = Mockery::mock(AuthService::class);
        $authService->shouldReceive('logout')->once()->andThrow(new \Exception('Erro inesperado'));

        $controller = new AuthControllerApi($authService);
        $response = $controller->logout();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(500, $response->status());

        $json = $response->getData(true);
        $this->assertEquals('Erro inesperado', $json['message']);
    }
}
