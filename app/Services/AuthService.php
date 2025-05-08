<?php

namespace App\Services;

use App\Models\Address;
use App\Repositories\Contracts\AddressRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;

class AuthService
{
    protected UserRepositoryInterface $userRepository;
    protected AddressRepositoryInterface $addressRepository;
    public function __construct(UserRepositoryInterface $userRepository, AddressRepositoryInterface $addressRepository)
    {
        $this->userRepository = $userRepository;
        $this->addressRepository = $addressRepository;
    }

    public function register(array $data)
    {
        try {
            $address = $this->addressRepository->create([
                'street' => $data['street'],
                'number' => $data['number'],
                'neighborhood' => $data['neighborhood'],
                'city' => $data['city'],
                'state' => $data['state'],
                'zipcode' => $data['zipcode'],
            ]);


            return $this->userRepository->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'cpf_cnpj' => $data['cpf_cnpj'],
                'password' => Hash::make($data['password']),
                'address_id' => $address->id,
            ]);
        } catch (\Exception $e) {
            Log::error("Erro ao registrar usuário: " . $e->getMessage());


            throw new \Exception("Não foi possível concluir o cadastro. Tente novamente.");
        }
    }

    public function login(array $credentials)
    {
        $user = $this->userRepository->findByEmail($credentials['email']);

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw new \Exception('Credenciais inválidas.');
        }

        $token = JWTAuth::fromUser($user);

        return [
            'user' => $user,
            'token' => 'Bearer ' . $token,
        ];
    }

    public function logout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
        } catch (TokenInvalidException | TokenExpiredException $e) {
            throw new \Exception('Token inválido ou expirado.', 401);
        } catch (JWTException $e) {
            throw new \Exception('Token ausente ou malformado.', 401);
        } catch (\Exception $e) {
            throw new \Exception('Erro interno ao fazer logout.', 500);
        }
    }
}
