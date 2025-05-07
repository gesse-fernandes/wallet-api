<?php

namespace Database\Factories;

use App\Models\Address;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $faker = \Faker\Factory::create('pt_BR');

        return [
            'name' => $faker->name,
            'email' => $faker->unique()->safeEmail(),
            'cpf_cnpj' => function () use ($faker) {
                $document = rand(0, 1)
                    ? $faker->cpf(false)      // gera CPF
                    : $faker->cnpj(false);    // ou CNPJ
                return preg_replace('/[^0-9]/', '', $document);
            },

            'password' => Hash::make('Senha123!'),
            'address_id' => Address::factory(),
            'balance' => 0,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
