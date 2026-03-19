<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Visit;
use Database\Seeders\WeeklyVisitsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeeklyVisitsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_weekly_visits_seeder_creates_schedule_for_current_week(): void
    {
        $this->seed(WeeklyVisitsSeeder::class);

        $this->assertSame(8, Visit::query()->count());
        $this->assertSame(8, Client::query()->count());

        $this->assertSame(
            8,
            Visit::query()
                ->whereBetween('visit_date', [now()->startOfWeek(), now()->endOfWeek()])
                ->count(),
        );

        $this->assertDatabaseHas('visits', [
            'service_type' => 'Планове ТО',
        ]);

        $this->assertDatabaseHas('visits', [
            'service_type' => 'Комп’ютерна діагностика',
        ]);
    }
}
