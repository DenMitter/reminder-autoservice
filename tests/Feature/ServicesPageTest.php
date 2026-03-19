<?php

namespace Tests\Feature;

use App\Livewire\ServicesPage;
use App\Models\ServiceCatalogItem;
use App\Models\User;
use Database\Seeders\ServiceCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ServicesPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_services_page_is_displayed_for_authenticated_users(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('services.index'))
            ->assertOk()
            ->assertSee('Додати послугу');
    }

    public function test_service_can_be_created(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ServicesPage::class)
            ->call('openCreateModal')
            ->set('name', 'Заміна ременя ГРМ')
            ->set('defaultPrice', '4200')
            ->call('saveService')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('service_catalog_items', [
            'name' => 'Заміна ременя ГРМ',
            'default_price' => '4200.00',
        ]);
    }

    public function test_service_can_be_updated(): void
    {
        $this->actingAs(User::factory()->create());

        $service = ServiceCatalogItem::factory()->create([
            'name' => 'Діагностика',
            'default_price' => 900,
        ]);

        Livewire::test(ServicesPage::class)
            ->call('openEditModal', $service->id)
            ->set('name', 'Комплексна діагностика')
            ->set('defaultPrice', '1200')
            ->call('saveService')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('service_catalog_items', [
            'id' => $service->id,
            'name' => 'Комплексна діагностика',
            'default_price' => '1200.00',
        ]);
    }

    public function test_service_can_be_deleted(): void
    {
        $this->actingAs(User::factory()->create());

        $service = ServiceCatalogItem::factory()->create();

        Livewire::test(ServicesPage::class)
            ->call('deleteService', $service->id);

        $this->assertDatabaseMissing('service_catalog_items', [
            'id' => $service->id,
        ]);
    }

    public function test_default_sto_services_can_be_seeded(): void
    {
        $this->seed(ServiceCatalogSeeder::class);

        $this->assertDatabaseHas('service_catalog_items', [
            'name' => 'Планове ТО',
            'default_price' => '1800.00',
        ]);

        $this->assertDatabaseHas('service_catalog_items', [
            'name' => 'Розвал-сходження',
            'default_price' => '800.00',
        ]);

        $this->assertDatabaseHas('service_catalog_items', [
            'name' => 'Заправка кондиціонера',
            'default_price' => '1200.00',
        ]);
    }
}
