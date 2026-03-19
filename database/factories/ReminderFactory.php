<?php

namespace Database\Factories;

use App\Enums\ReminderResponseStatus;
use App\Enums\ReminderStatus;
use App\Enums\ReminderType;
use App\Models\Client;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Reminder>
 */
class ReminderFactory extends Factory
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
            'visit_id' => Visit::factory(),
            'type' => ReminderType::Appointment,
            'send_at' => fake()->dateTimeBetween('-2 days', '+7 days'),
            'message' => fake()->sentence(),
            'status' => ReminderStatus::Pending,
            'sent_at' => null,
            'response_status' => ReminderResponseStatus::NoResponse,
        ];
    }

    public function sent(): self
    {
        return $this->state(fn (): array => [
            'status' => ReminderStatus::Sent,
            'sent_at' => now(),
        ]);
    }
}
