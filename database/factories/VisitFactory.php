<?php

namespace Database\Factories;

use App\Enums\VisitStatus;
use App\Models\Client;
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
}
