<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AuthService;
use App\Models\User;
use App\Models\Address;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\AddressRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthServiceTest extends TestCase
{
    use RefreshDatabase;
    public function test_register_user_successfully_()
    {
        // Usa factory mas NÃO persiste no banco
        $address = Address::factory()->make(); // make = não salva
        $user = User::factory()->make(['address_id' => null]); // sem persistência

        // Monta array com base nos models fakeados
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

        // Mocks dos repositórios
        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $addressRepo = $this->createMock(AddressRepositoryInterface::class);

        // Simula o retorno do Address criado
        $mockAddress = new Address(['id' => 1]);
        $addressRepo->method('create')->willReturn($mockAddress);

        // Simula o retorno do User criado
        $mockUser = new User([
            'id' => 1,
            'name' => $data['name'],
            'email' => $data['email'],
            'cpf_cnpj' => $data['cpf_cnpj'],
            'address_id' => $mockAddress->id,
        ]);
        $userRepo->method('create')->willReturn($mockUser);

        // Executa o serviço
        $service = new AuthService($userRepo, $addressRepo);
        $result = $service->register($data);

        // Valida
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($data['name'], $result->name);
        $this->assertEquals($mockAddress->id, $result->address_id);
    }
    public function test_register_throws_exception_if_address_creation_fails()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Não foi possível concluir o cadastro. Tente novamente.');

        $data = User::factory()->make()->toArray() + Address::factory()->make()->toArray();
        $data['password'] = 'Senha123!';

        $addressRepo = $this->createMock(AddressRepositoryInterface::class);
        $addressRepo->method('create')->willThrowException(new \Exception('Erro na criação de endereço'));

        $userRepo = $this->createMock(UserRepositoryInterface::class);

        $service = new AuthService($userRepo, $addressRepo);
        $service->register($data);
    }

    public function test_register_throws_exception_if_user_creation_fails()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Não foi possível concluir o cadastro. Tente novamente.');

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

        $addressRepo = $this->createMock(AddressRepositoryInterface::class);
        $addressRepo->method('create')->willReturn(new Address(['id' => 1]));

        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $userRepo->method('create')->willThrowException(new \Exception('Erro ao criar usuário'));

        $service = new AuthService($userRepo, $addressRepo);
        $service->register($data);
    }

    public function test_register_applies_password_hashing_correctly()
    {
        $address = Address::factory()->make();
        $user = User::factory()->make(['address_id' => null]);

        $plainPassword = 'Senha123!';
        $data = [
            'name' => $user->name,
            'email' => $user->email,
            'cpf_cnpj' => $user->cpf_cnpj,
            'password' => $plainPassword,
            'street' => $address->street,
            'number' => $address->number,
            'neighborhood' => $address->neighborhood,
            'city' => $address->city,
            'state' => $address->state,
            'zipcode' => $address->zipcode,
        ];

        $addressRepo = $this->createMock(AddressRepositoryInterface::class);
        $addressRepo->method('create')->willReturn(new Address(['id' => 1]));

        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $userRepo->method('create')->willReturnCallback(function ($attributes) use ($plainPassword) {
            \PHPUnit\Framework\Assert::assertTrue(\Illuminate\Support\Facades\Hash::check($plainPassword, $attributes['password']));
            $user = new User(['name' => $attributes['name']]);
            $user->id = 10;
            return $user;
        });

        $service = new AuthService($userRepo, $addressRepo);
        $result = $service->register($data);

        $this->assertEquals(10, $result->id);
    }
    public function test_login_generates_valid_jwt_token_for_valid_credentials()
    {
        $password = 'Senha123!';
        $user = User::factory()->create([
            'password' => Hash::make($password),
        ]);

        // Repositórios reais, sem mocks
        $userRepo = new class implements \App\Repositories\Contracts\UserRepositoryInterface {
            public function create($data) {}
            public function findByEmail($email)
            {
                return User::where('email', $email)->first();
            }
        };

        // Address não é usado no login
        $addressRepo = $this->createMock(\App\Repositories\Contracts\AddressRepositoryInterface::class);

        $service = new AuthService($userRepo, $addressRepo);

        $result = $service->login([
            'email' => $user->email,
            'password' => $password,
        ]);

        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('token', $result);
        $this->assertEquals($user->id, $result['user']->id);
        $this->assertStringStartsWith('Bearer ', $result['token']);
    }

    public function test_login_throws_exception_on_invalid_credentials()
    {
        $password = 'Senha123!';
        $user = User::factory()->create([
            'email' => 'fail@email.com',
            'password' => Hash::make($password),
        ]);

        $userRepo = new class implements \App\Repositories\Contracts\UserRepositoryInterface {
            public function create($data) {}
            public function findByEmail($email)
            {
                return User::where('email', $email)->first();
            }
        };

        $addressRepo = $this->createMock(\App\Repositories\Contracts\AddressRepositoryInterface::class);
        $service = new AuthService($userRepo, $addressRepo);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Credenciais inválidas.');

        $service->login([
            'email' => $user->email,
            'password' => 'senhaErrada',
        ]);
    }
    public function test_logout_invalidates_valid_token()
    {
        // Cria usuário real
        $user = User::factory()->create();

        // Gera token real
        $token = JWTAuth::fromUser($user);

        // Seta o token como o atual
        JWTAuth::setToken($token);

        // Mocka os repositórios (não usados no logout)
        $userRepo = $this->createMock(\App\Repositories\Contracts\UserRepositoryInterface::class);
        $addressRepo = $this->createMock(\App\Repositories\Contracts\AddressRepositoryInterface::class);

        $service = new \App\Services\AuthService($userRepo, $addressRepo);

        // Executa logout
        $service->logout();

        // Tenta usar o token depois do logout para validar que foi invalidado
        $this->expectException(\Tymon\JWTAuth\Exceptions\TokenInvalidException::class);
        JWTAuth::setToken($token)->authenticate();
    }

    public function test_logout_with_invalid_token_throws_exception()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Token inválido ou expirado.');

        $fakeToken = 'xxxxx.yyyy.zzzz';
        JWTAuth::setToken($fakeToken);

        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $addressRepo = $this->createMock(AddressRepositoryInterface::class);

        $service = new AuthService($userRepo, $addressRepo);
        $service->logout();
    }


    public function test_logout_without_token_throws_exception()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Token ausente ou malformado.');

        // Nenhum token setado
        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $addressRepo = $this->createMock(AddressRepositoryInterface::class);

        $service = new AuthService($userRepo, $addressRepo);
        $service->logout();
    }
}
