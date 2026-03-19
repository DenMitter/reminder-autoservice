<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'phone' => fake()->unique()->numerify('+380#########'),
            'car_brand' => fake()->randomElement(['Toyota', 'BMW', 'Volkswagen', 'Renault', 'Ford']),
            'car_model' => fake()->bothify('Model-##'),
            'car_number' => fake()->optional()->bothify('AA####AA'),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
