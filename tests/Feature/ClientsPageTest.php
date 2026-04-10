<?php

namespace Tests\Feature;

use App\Livewire\ClientsPage;
use App\Models\Client;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClientsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_be_created_with_multiple_vehicles(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ClientsPage::class)
            ->call('openCreateModal')
            ->set('fullName', 'Іван Коваленко')
            ->set('phone', '+380991234567')
            ->set('vehicles.0.carBrand', 'Toyota')
            ->set('vehicles.0.carModel', 'Camry')
            ->set('vehicles.0.carNumber', 'AA1111AA')
            ->call('addVehicle')
            ->set('vehicles.1.carBrand', 'BMW')
            ->set('vehicles.1.carModel', 'X5')
            ->set('vehicles.1.carNumber', 'AA2222BB')
            ->call('saveClient')
            ->assertHasNoErrors();

        $client = Client::query()->firstOrFail();

        $this->assertSame('Toyota', $client->car_brand);
        $this->assertSame('Camry', $client->car_model);
        $this->assertSame(2, $client->vehicles()->count());
        $this->assertDatabaseHas('client_vehicles', [
            'client_id' => $client->id,
            'car_brand' => 'Toyota',
            'car_model' => 'Camry',
            'car_number' => 'AA1111AA',
        ]);
        $this->assertDatabaseHas('client_vehicles', [
            'client_id' => $client->id,
            'car_brand' => 'BMW',
            'car_model' => 'X5',
            'car_number' => 'AA2222BB',
        ]);
    }

    public function test_client_profile_shows_all_client_vehicles_and_visit_vehicle(): void
    {
        $this->actingAs(User::factory()->create());

        $client = Client::factory()->create([
            'full_name' => 'Олена Петренко',
        ]);
        $primaryVehicle = $client->vehicles()->firstOrFail();
        $secondVehicle = $client->vehicles()->create([
            'car_brand' => 'Audi',
            'car_model' => 'A6',
            'car_number' => 'AA0006TT',
        ]);

        Visit::factory()->create([
            'client_id' => $client->id,
            'client_vehicle_id' => $secondVehicle->id,
            'service_type' => 'Діагностика',
        ]);

        $this->get(route('clients.show', $client))
            ->assertOk()
            ->assertSee($primaryVehicle->car_brand)
            ->assertSee($primaryVehicle->car_model)
            ->assertSee('Audi')
            ->assertSee('A6')
            ->assertSee('AA0006TT')
            ->assertSee('Діагностика');
    }
}
