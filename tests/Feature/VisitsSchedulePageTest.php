<?php

namespace Tests\Feature;

use App\Enums\ReminderStatus;
use App\Enums\ReminderType;
use App\Livewire\VisitsSchedulePage;
use App\Models\Client;
use App\Models\Reminder;
use App\Models\ServiceCatalogItem;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VisitsSchedulePageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('sms.driver', 'log');
        config()->set('app.name', 'Автомаксимум');
        config()->set('app.service_address', 'Гетьмана Мазепи 24');
        config()->set('app.service_phone', '+380957192999');
    }

    public function test_schedule_page_is_displayed_for_authenticated_users(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('visits.schedule'))
            ->assertOk()
            ->assertSee('Записати клієнта');
    }

    public function test_visits_index_redirects_to_schedule_page(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('visits.index'))
            ->assertRedirect(route('visits.schedule'));
    }

    public function test_visit_can_be_created_for_an_existing_client_from_the_schedule(): void
    {
        $this->actingAs(User::factory()->create());

        $client = Client::factory()->create();
        $service = ServiceCatalogItem::factory()->create([
            'name' => 'Діагностика',
            'default_price' => 1200,
        ]);

        Livewire::test(VisitsSchedulePage::class)
            ->call('openCreateModal', now()->addDay()->toDateString(), '10:00', '11:30')
            ->set('clientMode', 'existing')
            ->set('existingClientId', $client->id)
            ->set('serviceType', $service->name)
            ->set('cameFromReminder', true)
            ->assertSet('price', '1200.00')
            ->call('createVisit')
            ->assertHasNoErrors();

        $visit = Visit::query()->first();

        $this->assertNotNull($visit);
        $this->assertSame($client->id, $visit->client_id);
        $this->assertSame('Діагностика', $visit->service_type);
        $this->assertTrue($visit->came_from_reminder);
        $this->assertSame('10:00', $visit->visit_date->format('H:i'));
        $this->assertSame('11:30', $visit->visit_end_at?->format('H:i'));
        $this->assertDatabaseHas('reminders', [
            'visit_id' => $visit->id,
            'client_id' => $client->id,
            'type' => ReminderType::Appointment->value,
            'send_at' => $visit->visit_date->copy()->subHour()->format('Y-m-d H:i:s'),
        ]);
        $this->assertDatabaseHas('reminders', [
            'visit_id' => $visit->id,
            'client_id' => $client->id,
            'type' => ReminderType::AppointmentConfirmation->value,
            'status' => ReminderStatus::Sent->value,
        ]);
    }

    public function test_visit_can_be_created_with_a_new_client_from_the_schedule(): void
    {
        $this->actingAs(User::factory()->create());
        ServiceCatalogItem::factory()->create([
            'name' => 'Планове ТО',
            'default_price' => 2200,
        ]);

        $component = Livewire::test(VisitsSchedulePage::class)
            ->call('openCreateModal', now()->startOfWeek()->addDay()->toDateString(), '12:00', '13:00')
            ->set('fullName', 'Іван Петренко')
            ->set('phone', '+380991112233')
            ->set('carBrand', 'BMW')
            ->set('carModel', 'X5')
            ->set('serviceType', 'Планове ТО')
            ->call('createVisit')
            ->assertHasNoErrors();

        $component->assertSet('clientMode', 'new');

        $this->assertDatabaseHas('clients', [
            'full_name' => 'Іван Петренко',
            'phone' => '+380991112233',
            'car_brand' => 'BMW',
            'car_model' => 'X5',
        ]);

        $this->assertDatabaseHas('visits', [
            'service_type' => 'Планове ТО',
        ]);
    }

    public function test_existing_client_hint_is_shown_when_phone_matches_during_typing(): void
    {
        $this->actingAs(User::factory()->create());

        $client = Client::factory()->create([
            'full_name' => 'Олександр Бондаренко',
            'phone' => '+380957192907',
            'car_brand' => 'Toyota',
            'car_model' => 'Land Cruiser',
        ]);

        Livewire::test(VisitsSchedulePage::class)
            ->call('openCreateModal', now()->startOfWeek()->addDay()->toDateString(), '12:00', '13:00')
            ->set('phone', '380957192907')
            ->assertSee('Клієнт з таким номером уже існує')
            ->assertSee($client->full_name)
            ->call('useExistingClientFromPhoneMatch')
            ->assertSet('clientMode', 'existing')
            ->assertSet('existingClientId', $client->id);
    }

    public function test_appointment_reminder_is_sent_immediately_when_visit_starts_within_an_hour(): void
    {
        $this->actingAs(User::factory()->create());
        $this->travelTo(now()->startOfDay()->setTime(18, 47));

        ServiceCatalogItem::factory()->create([
            'name' => 'Планове ТО',
            'default_price' => 2200,
        ]);

        Livewire::test(VisitsSchedulePage::class)
            ->call('openCreateModal', now()->toDateString(), '19:00', '20:00')
            ->set('fullName', 'Іван Петренко')
            ->set('phone', '+380991112233')
            ->set('carBrand', 'BMW')
            ->set('carModel', 'X5')
            ->set('serviceType', 'Планове ТО')
            ->call('createVisit')
            ->assertHasNoErrors();

        $visit = Visit::query()->latest('id')->firstOrFail();
        $reminder = Reminder::query()
            ->where('visit_id', $visit->id)
            ->where('type', ReminderType::Appointment)
            ->firstOrFail();

        $this->assertSame(ReminderStatus::Sent->value, $reminder->status->value);
        $this->assertNotNull($reminder->sent_at);
        $this->assertSame(now()->format('Y-m-d H:i:s'), $reminder->send_at->format('Y-m-d H:i:s'));
        $this->assertDatabaseHas('reminders', [
            'visit_id' => $visit->id,
            'type' => ReminderType::AppointmentConfirmation->value,
            'status' => ReminderStatus::Sent->value,
        ]);

        $this->travelBack();
    }

    public function test_visit_reminders_use_booking_and_upcoming_templates(): void
    {
        $this->actingAs(User::factory()->create());

        ServiceCatalogItem::factory()->create([
            'name' => 'Заміна масла',
            'default_price' => 500,
        ]);

        Livewire::test(VisitsSchedulePage::class)
            ->call('openCreateModal', now()->addDay()->toDateString(), '14:00', '15:00')
            ->set('fullName', 'Іван Петренко')
            ->set('phone', '+380991112233')
            ->set('carBrand', 'BMW')
            ->set('carModel', 'X5')
            ->set('serviceType', 'Заміна масла')
            ->call('createVisit')
            ->assertHasNoErrors();

        $visit = Visit::query()->latest('id')->firstOrFail();

        $this->assertDatabaseHas('reminders', [
            'visit_id' => $visit->id,
            'type' => ReminderType::AppointmentConfirmation->value,
            'message' => "Ви записані на сервіс Автомаксимум {$visit->visit_date->locale('uk')->isoFormat('D MMMM')} о 14:00\nГетьмана Мазепи 24",
        ]);
        $this->assertDatabaseHas('reminders', [
            'visit_id' => $visit->id,
            'type' => ReminderType::Appointment->value,
            'message' => "Очікуємо Вас через 1год ;)\nАвтомаксимум, Гетьмана Мазепи 24\n+380957192999",
        ]);
    }

    public function test_model_is_reset_when_brand_changes(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(VisitsSchedulePage::class)
            ->set('carBrand', 'BMW')
            ->set('carModel', 'X5')
            ->set('carBrand', 'Toyota')
            ->assertSet('carModel', '');
    }

    public function test_vehicle_brands_and_models_are_loaded_from_local_catalog_and_saved_clients(): void
    {
        $this->actingAs(User::factory()->create());

        Client::factory()->create([
            'car_brand' => 'Rivian',
            'car_model' => 'R1T',
        ]);

        Livewire::test(VisitsSchedulePage::class)
            ->call('openCreateModal', now()->startOfWeek()->toDateString(), '09:00', '10:00')
            ->assertSee('BMW')
            ->assertSee('Rivian')
            ->set('carBrand', 'Rivian')
            ->assertSee('R1T');
    }

    public function test_visit_can_be_created_with_custom_brand_and_model(): void
    {
        $this->actingAs(User::factory()->create());
        ServiceCatalogItem::factory()->create([
            'name' => 'Огляд',
            'default_price' => 700,
        ]);

        Livewire::test(VisitsSchedulePage::class)
            ->call('openCreateModal', now()->startOfWeek()->addDay()->toDateString(), '14:00', '15:00')
            ->set('fullName', 'Петро Іваненко')
            ->set('phone', '+380971112233')
            ->set('carBrand', '__custom_brand__')
            ->set('customCarBrand', 'Rivian')
            ->set('carModel', '__custom_model__')
            ->set('customCarModel', 'R1S')
            ->set('serviceType', 'Огляд')
            ->call('createVisit')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('clients', [
            'full_name' => 'Петро Іваненко',
            'car_brand' => 'Rivian',
            'car_model' => 'R1S',
        ]);
    }

    public function test_new_service_can_be_added_from_the_schedule_booking_form(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(VisitsSchedulePage::class)
            ->call('openCreateModal', now()->startOfWeek()->addDay()->toDateString(), '14:00', '15:00')
            ->set('fullName', 'Петро Іваненко')
            ->set('phone', '+380971112233')
            ->set('carBrand', 'Toyota')
            ->set('carModel', 'Camry')
            ->set('serviceType', '__custom_service__')
            ->set('customServiceType', 'Розвал-сходження')
            ->set('price', '900')
            ->call('createVisit')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('service_catalog_items', [
            'name' => 'Розвал-сходження',
            'default_price' => '900.00',
        ]);

        $this->assertDatabaseHas('visits', [
            'service_type' => 'Розвал-сходження',
            'price' => '900.00',
        ]);
    }

    public function test_visit_details_can_be_opened(): void
    {
        $this->actingAs(User::factory()->create());

        $visit = Visit::factory()->create();

        $component = Livewire::test(VisitsSchedulePage::class)
            ->call('openVisitDetails', $visit->id)
            ->assertSet('selectedVisitId', $visit->id)
            ->assertSet('showVisitDetailsModal', true)
            ->assertSee($visit->client->full_name);

        $this->assertSame(1, substr_count($component->html(), '>Авто<'));
    }

    public function test_visit_details_can_be_updated_from_schedule_page(): void
    {
        $this->actingAs(User::factory()->create());

        ServiceCatalogItem::factory()->create([
            'name' => 'Заміна масла',
            'default_price' => 1500,
        ]);

        $visit = Visit::factory()->create([
            'service_type' => 'Діагностика',
            'visit_date' => now()->startOfWeek()->setTime(10, 0),
            'visit_end_at' => now()->startOfWeek()->setTime(11, 0),
            'status' => 'planned',
            'price' => 500,
            'next_service_date' => now()->addMonth()->toDateString(),
            'notes' => 'Старі нотатки',
        ]);

        Livewire::test(VisitsSchedulePage::class)
            ->call('openVisitDetails', $visit->id)
            ->set('editServiceType', 'Заміна масла')
            ->assertSet('editPrice', '1500.00')
            ->set('editStatus', 'completed')
            ->set('editCameFromReminder', true)
            ->set('editStartTime', '09:30')
            ->set('editEndTime', '11:30')
            ->set('editVisitNotes', 'Оновлені нотатки')
            ->call('saveVisitDetails')
            ->assertHasNoErrors()
            ->assertSet('editServiceType', 'Заміна масла')
            ->assertSet('editStatus', 'completed');

        $visit->refresh();

        $this->assertSame('Заміна масла', $visit->service_type);
        $this->assertSame('completed', $visit->status->value);
        $this->assertTrue($visit->came_from_reminder);
        $this->assertSame('09:30', $visit->visit_date->format('H:i'));
        $this->assertSame('11:30', $visit->visit_end_at?->format('H:i'));
        $this->assertSame('1500.00', (string) $visit->price);
        $this->assertSame('Оновлені нотатки', $visit->notes);
        $this->assertSame(now()->addMonth()->toDateString(), $visit->next_service_date?->toDateString());
    }

    public function test_new_service_can_be_added_from_visit_details(): void
    {
        $this->actingAs(User::factory()->create());

        $visit = Visit::factory()->create([
            'service_type' => 'Діагностика',
            'visit_date' => now()->startOfWeek()->setTime(10, 0),
            'visit_end_at' => now()->startOfWeek()->setTime(11, 0),
        ]);

        Livewire::test(VisitsSchedulePage::class)
            ->call('openVisitDetails', $visit->id)
            ->set('editServiceType', '__custom_service__')
            ->set('editCustomServiceType', 'Чистка форсунок')
            ->set('editPrice', '1800')
            ->call('saveVisitDetails')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('service_catalog_items', [
            'name' => 'Чистка форсунок',
            'default_price' => '1800.00',
        ]);

        $this->assertDatabaseHas('visits', [
            'id' => $visit->id,
            'service_type' => 'Чистка форсунок',
            'price' => '1800.00',
        ]);
    }

    public function test_visit_time_can_be_resized(): void
    {
        $this->actingAs(User::factory()->create());

        $visit = Visit::factory()->create([
            'visit_date' => now()->startOfWeek()->setTime(10, 0),
            'visit_end_at' => now()->startOfWeek()->setTime(11, 0),
        ]);

        Livewire::test(VisitsSchedulePage::class)
            ->call('updateVisitTiming', $visit->id, $visit->visit_date->toDateString(), '09:30', '11:30');

        $visit->refresh();

        $this->assertSame('09:30', $visit->visit_date->format('H:i'));
        $this->assertSame('11:30', $visit->visit_end_at?->format('H:i'));
    }

    public function test_visit_can_be_moved_between_days_and_hours(): void
    {
        $this->actingAs(User::factory()->create());

        $visit = Visit::factory()->create([
            'visit_date' => now()->startOfWeek()->setTime(10, 0),
            'visit_end_at' => now()->startOfWeek()->setTime(11, 30),
        ]);
        $reminder = Reminder::factory()->create([
            'client_id' => $visit->client_id,
            'visit_id' => $visit->id,
            'type' => ReminderType::Appointment,
            'send_at' => $visit->visit_date->copy()->subHour(),
        ]);

        Livewire::test(VisitsSchedulePage::class)
            ->call('moveVisit', $visit->id, now()->startOfWeek()->addDays(3)->toDateString(), '14:00');

        $visit->refresh();
        $reminder->refresh();

        $this->assertSame(now()->startOfWeek()->addDays(3)->toDateString(), $visit->visit_date->toDateString());
        $this->assertSame('14:00', $visit->visit_date->format('H:i'));
        $this->assertSame('15:30', $visit->visit_end_at?->format('H:i'));
        $this->assertSame('13:00', $reminder->send_at->format('H:i'));
        $this->assertSame(now()->startOfWeek()->addDays(3)->toDateString(), $reminder->send_at->toDateString());
    }

    public function test_appointment_reminder_is_removed_when_visit_is_no_longer_planned(): void
    {
        $this->actingAs(User::factory()->create());

        $visit = Visit::factory()->create([
            'status' => 'planned',
            'visit_date' => now()->startOfWeek()->setTime(10, 0),
            'visit_end_at' => now()->startOfWeek()->setTime(11, 0),
        ]);

        Reminder::factory()->create([
            'client_id' => $visit->client_id,
            'visit_id' => $visit->id,
            'type' => ReminderType::Appointment,
            'send_at' => $visit->visit_date->copy()->subHour(),
        ]);
        Reminder::factory()->create([
            'client_id' => $visit->client_id,
            'visit_id' => $visit->id,
            'type' => ReminderType::AppointmentConfirmation,
            'send_at' => now(),
        ]);

        Livewire::test(VisitsSchedulePage::class)
            ->call('openVisitDetails', $visit->id)
            ->set('editServiceType', $visit->service_type)
            ->set('editStatus', 'completed')
            ->set('editStartTime', '10:00')
            ->set('editEndTime', '11:00')
            ->call('saveVisitDetails')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('reminders', [
            'visit_id' => $visit->id,
            'type' => ReminderType::Appointment->value,
        ]);
        $this->assertDatabaseMissing('reminders', [
            'visit_id' => $visit->id,
            'type' => ReminderType::AppointmentConfirmation->value,
            'status' => ReminderStatus::Pending->value,
        ]);
    }

    public function test_schedule_blocks_use_background_colors_based_on_status(): void
    {
        $this->actingAs(User::factory()->create());

        Visit::factory()->create([
            'status' => 'completed',
            'visit_date' => now()->startOfWeek()->setTime(10, 0),
            'visit_end_at' => now()->startOfWeek()->setTime(11, 0),
        ]);

        Visit::factory()->create([
            'status' => 'cancelled',
            'visit_date' => now()->startOfWeek()->addDay()->setTime(12, 0),
            'visit_end_at' => now()->startOfWeek()->addDay()->setTime(13, 0),
        ]);

        Visit::factory()->create([
            'status' => 'planned',
            'visit_date' => now()->startOfWeek()->addDays(2)->setTime(14, 0),
            'visit_end_at' => now()->startOfWeek()->addDays(2)->setTime(15, 0),
        ]);

        $this->get(route('visits.schedule'))
            ->assertOk()
            ->assertSee('bg-emerald-100/90', false)
            ->assertSee('bg-rose-100/90', false)
            ->assertSee('bg-zinc-900/6', false);
    }

    public function test_half_hour_visit_uses_compact_schedule_block_layout(): void
    {
        $this->actingAs(User::factory()->create());

        Visit::factory()->create([
            'visit_date' => now()->startOfWeek()->setTime(11, 0),
            'visit_end_at' => now()->startOfWeek()->setTime(11, 30),
            'service_type' => 'Заміна масла',
        ]);

        $this->get(route('visits.schedule'))
            ->assertOk()
            ->assertSee('data-compact="true"', false)
            ->assertSee('leading-tight', false);
    }
}
