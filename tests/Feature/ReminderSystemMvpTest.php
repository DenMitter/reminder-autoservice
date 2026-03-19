<?php

namespace Tests\Feature;

use App\Enums\ReminderResponseStatus;
use App\Enums\ReminderStatus;
use App\Enums\ReminderType;
use App\Enums\VisitStatus;
use App\Livewire\RemindersPage;
use App\Livewire\VisitsPage;
use App\Models\Client;
use App\Models\Reminder;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class ReminderSystemMvpTest extends TestCase
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

    public function test_admin_can_create_client_visit_and_appointment_reminder(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();

        $this->actingAs($user);

        Livewire::test(VisitsPage::class)
            ->set('clientId', $client->id)
            ->set('serviceType', 'Oil change')
            ->set('visitDate', now()->addDay()->format('Y-m-d\\TH:i'))
            ->set('price', '1200')
            ->set('status', VisitStatus::Planned->value)
            ->set('createAppointmentReminder', true)
            ->set('appointmentReminderDate', now()->addHours(4)->format('Y-m-d\\TH:i'))
            ->call('createVisit')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('visits', 1);
        $this->assertDatabaseHas('reminders', [
            'client_id' => $client->id,
            'type' => ReminderType::Appointment->value,
            'status' => ReminderStatus::Pending->value,
        ]);
    }

    public function test_scheduler_command_sends_due_pending_reminders(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();

        $this->actingAs($user);

        Reminder::query()->create([
            'client_id' => $client->id,
            'visit_id' => null,
            'type' => ReminderType::Appointment,
            'send_at' => now()->subMinute(),
            'message' => 'Test reminder',
            'status' => ReminderStatus::Pending,
            'response_status' => ReminderResponseStatus::NoResponse,
        ]);

        $this->artisan('reminders:process')
            ->assertExitCode(0);

        $this->assertDatabaseHas('reminders', [
            'client_id' => $client->id,
            'status' => ReminderStatus::Sent->value,
        ]);
    }

    public function test_sms_service_sends_alphasms_request_in_expected_format(): void
    {
        Http::fake([
            'https://alphasms.ua/api/json.php' => Http::response([
                'success' => true,
                'data' => [
                    ['success' => true],
                ],
            ]),
        ]);

        config()->set('sms.driver', 'alphasms');
        config()->set('sms.alphasms.api_key', 'test-api-key');
        config()->set('sms.alphasms.sender', 'AwixCode');
        config()->set('sms.alphasms.endpoint', 'https://alphasms.ua/api/json.php');

        $reminder = Reminder::factory()->create([
            'message' => 'Тестове нагадування',
        ]);

        $sent = app(SmsService::class)->send($reminder);

        $this->assertTrue($sent);

        Http::assertSent(function ($request) use ($reminder) {
            $data = $request->data();

            return $request->url() === 'https://alphasms.ua/api/json.php'
                && $data['auth'] === 'test-api-key'
                && $data['data'][0]['type'] === 'sms'
                && $data['data'][0]['phone'] === '380'.substr(preg_replace('/\D+/', '', $reminder->client->phone), -9)
                && $data['data'][0]['sms_signature'] === 'AwixCode'
                && $data['data'][0]['sms_message'] === 'Тестове нагадування';
        });
    }

    public function test_sms_service_marks_reminder_failed_when_alphasms_rejects_nested_result(): void
    {
        Http::fake([
            'https://alphasms.ua/api/json.php' => Http::response([
                'success' => true,
                'data' => [
                    ['success' => false, 'error' => 'Invalid alpha name'],
                ],
            ]),
        ]);

        config()->set('sms.driver', 'alphasms');
        config()->set('sms.alphasms.api_key', 'test-api-key');
        config()->set('sms.alphasms.sender', 'AwixCode');
        config()->set('sms.alphasms.endpoint', 'https://alphasms.ua/api/json.php');

        $reminder = Reminder::factory()->create([
            'message' => 'Тестове нагадування',
        ]);

        $sent = app(SmsService::class)->send($reminder);

        $this->assertFalse($sent);
        $this->assertDatabaseHas('reminders', [
            'id' => $reminder->id,
            'status' => ReminderStatus::Failed->value,
        ]);
    }

    public function test_admin_can_mark_client_came_and_dashboard_shows_returned_statistic(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();
        $reminder = Reminder::factory()->create([
            'client_id' => $client->id,
            'status' => ReminderStatus::Sent,
        ]);

        $this->actingAs($user);

        Livewire::test(RemindersPage::class)
            ->call('updateResponseStatus', $reminder->id, ReminderResponseStatus::ClientCame->value)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('reminders', [
            'id' => $reminder->id,
            'response_status' => ReminderResponseStatus::ClientCame->value,
        ]);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Клієнтів повернулося')
            ->assertSee('1');
    }
}
