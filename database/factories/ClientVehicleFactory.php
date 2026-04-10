<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ClientVehicle>
 */
class ClientVehicleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'car_brand' => fake()->randomElement(['Toyota', 'BMW', 'Volkswagen', 'Renault', 'Ford']),
            'car_model' => fake()->bothify('Model-##'),
            'car_number' => fake()->optional()->bothify('AA####AA'),
        ];
    }
}
