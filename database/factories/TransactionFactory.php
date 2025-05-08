<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'payer_id' => $this->faker->boolean ? User::factory() : null,
            'payee_id' => User::factory(),
            'amount' => $this->faker->randomFloat(2, 1, 5000),
            'type' => $this->faker->randomElement(['deposit', 'transfer', 'reversal']),
            'status' => $this->faker->randomElement(['pending', 'completed', 'reversed']),
            'reversed_transaction_id' => null,
            'metadata' => [
                'ip' => $this->faker->ipv4,
                'user_agent' => $this->faker->userAgent,
                'descricao' => $this->faker->sentence,
            ],
        ];
    }
}
