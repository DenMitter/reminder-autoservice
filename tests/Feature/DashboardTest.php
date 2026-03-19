<?php

namespace Tests\Feature;

use App\Livewire\DashboardPage;
use App\Models\Client;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response
            ->assertOk()
            ->assertSee('xl:grid-cols-4', false)
            ->assertSee('Початок')
            ->assertSee('Графік статистики')
            ->assertDontSee('Нагадування на сьогодні')
            ->assertDontSee('Найближчі нагадування');
    }

    public function test_dashboard_counts_returned_clients_from_visit_checkbox(): void
    {
        $this->actingAs(User::factory()->create());

        $client = Client::factory()->create();

        Visit::factory()->create([
            'client_id' => $client->id,
            'came_from_reminder' => true,
        ]);

        Visit::factory()->create([
            'client_id' => $client->id,
            'came_from_reminder' => true,
        ]);

        Visit::factory()->create([
            'came_from_reminder' => true,
        ]);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Клієнтів повернулося')
            ->assertSee('2');
    }

    public function test_dashboard_filters_statistics_by_selected_period_and_shows_revenue(): void
    {
        $this->actingAs(User::factory()->create());

        Visit::factory()->create([
            'visit_date' => now()->startOfMonth()->addDay()->setTime(10, 0),
            'status' => 'completed',
            'price' => 1500,
            'came_from_reminder' => true,
        ]);

        Visit::factory()->create([
            'visit_date' => now()->startOfMonth()->addDays(2)->setTime(12, 0),
            'status' => 'completed',
            'price' => 2200,
            'did_not_show' => true,
        ]);

        Visit::factory()->create([
            'visit_date' => now()->subMonth()->setTime(9, 0),
            'status' => 'completed',
            'price' => 9900,
            'came_from_reminder' => true,
            'did_not_show' => true,
        ]);

        Livewire::test(DashboardPage::class)
            ->set('startDate', now()->startOfMonth()->toDateString())
            ->set('endDate', now()->endOfMonth()->toDateString())
            ->assertSee('Виручка за період')
            ->assertSee('3 700,00 грн')
            ->assertSee('Клієнтів повернулося')
            ->assertSee('Графік статистики');
    }

    public function test_dashboard_period_preset_updates_dates(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(DashboardPage::class)
            ->call('usePeriodPreset', 'week')
            ->assertSet('startDate', now()->startOfWeek()->toDateString())
            ->assertSet('endDate', now()->toDateString())
            ->call('usePeriodPreset', 'month')
            ->assertSet('startDate', now()->startOfMonth()->toDateString())
            ->assertSet('endDate', now()->toDateString())
            ->call('usePeriodPreset', 'year')
            ->assertSet('startDate', now()->startOfYear()->toDateString())
            ->assertSet('endDate', now()->toDateString());
    }
}
