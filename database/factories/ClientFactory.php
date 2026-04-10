<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\ClientVehicle;
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

    public function configure(): static
    {
        return $this->afterCreating(function (Client $client): void {
            if ($client->vehicles()->exists() || blank($client->car_brand) || blank($client->car_model)) {
                return;
            }

            ClientVehicle::query()->create([
                'client_id' => $client->id,
                'car_brand' => $client->car_brand,
                'car_model' => $client->car_model,
                'car_number' => $client->car_number,
            ]);
        });
    }
}
