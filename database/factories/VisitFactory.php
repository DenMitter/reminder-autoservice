<?php

namespace Database\Factories;

use App\Enums\VisitStatus;
use App\Models\Client;
use App\Models\ClientVehicle;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Visit>
 */
class VisitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $visitDate = fake()->dateTimeBetween('-30 days', '+30 days');

        return [
            'client_id' => Client::factory(),
            'service_type' => fake()->randomElement(['Oil change', 'Diagnostics', 'Brake service', 'Suspension']),
            'visit_date' => $visitDate,
            'visit_end_at' => (clone $visitDate)->modify('+1 hour'),
            'price' => fake()->randomFloat(2, 500, 12000),
            'status' => VisitStatus::Planned,
            'next_service_date' => fake()->optional()->dateTimeBetween('+30 days', '+180 days'),
            'notes' => fake()->optional()->sentence(),
            'came_from_reminder' => false,
            'did_not_show' => false,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Visit $visit): void {
            if ($visit->client_vehicle_id !== null) {
                return;
            }

            $clientVehicle = $visit->client->vehicles()->first();

            if ($clientVehicle === null) {
                $clientVehicle = ClientVehicle::query()->create([
                    'client_id' => $visit->client->id,
                    'car_brand' => $visit->client->car_brand,
                    'car_model' => $visit->client->car_model,
                    'car_number' => $visit->client->car_number,
                ]);
            }

            $visit->update([
                'client_vehicle_id' => $clientVehicle->id,
            ]);
        });
    }
}
