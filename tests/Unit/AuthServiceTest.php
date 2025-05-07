<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AuthService;
use App\Models\User;
use App\Models\Address;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\AddressRepositoryInterface;

class AuthServiceTest extends TestCase
{

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
}
