<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Address>
 */
class AddressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $faker = \Faker\Factory::create('pt_BR');

        return [
            'street' => $faker->streetName(),
            'number' => $faker->buildingNumber(),
            'neighborhood' => $faker->citySuffix(),
            'city' => $faker->city(),
            'state' => $faker->stateAbbr(),
            'zipcode' => $faker->postcode(),
        ];
    }
}
