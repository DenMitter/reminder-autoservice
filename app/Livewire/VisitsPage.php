<?php

namespace App\Livewire;

use App\Enums\ReminderResponseStatus;
use App\Enums\ReminderStatus;
use App\Enums\ReminderType;
use App\Enums\VisitStatus;
use App\Models\Client;
use App\Models\Reminder;
use App\Models\Visit;
use Illuminate\Support\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Visits')]
class VisitsPage extends Component
{
    use WithPagination;

    public int $clientId = 0;
    public string $serviceType = '';
    public string $visitDate = '';
    public ?string $price = null;
    public string $status = 'planned';
    public ?string $nextServiceDate = null;
    public ?string $notes = null;

    public bool $createAppointmentReminder = true;
    public ?string $appointmentReminderDate = null;

    /**
     * @var array<int, string>
     */
    public array $statusUpdates = [];

    /**
     * @var array<int, string|null>
     */
    public array $nextServiceUpdates = [];

    public function mount(): void
    {
        $this->visitDate = now()->addHour()->format('Y-m-d\\TH:i');
        $this->appointmentReminderDate = now()->format('Y-m-d\\TH:i');
    }

    public function createVisit(): void
    {
        $validated = $this->validate([
            'clientId' => ['required', 'exists:clients,id'],
            'serviceType' => ['required', 'string', 'max:255'],
            'visitDate' => ['required', 'date'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:planned,completed,cancelled'],
            'nextServiceDate' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'createAppointmentReminder' => ['boolean'],
            'appointmentReminderDate' => ['nullable', 'date'],
        ]);

        $visit = Visit::query()->create([
            'client_id' => $validated['clientId'],
            'service_type' => $validated['serviceType'],
            'visit_date' => $validated['visitDate'],
            'visit_end_at' => Carbon::parse($validated['visitDate'])->addHour(),
            'price' => $validated['price'],
            'status' => $validated['status'],
            'next_service_date' => $validated['nextServiceDate'],
            'notes' => $validated['notes'],
        ]);

        if ($validated['createAppointmentReminder'] && $validated['appointmentReminderDate'] !== null) {
            Reminder::query()->create([
                'client_id' => $visit->client_id,
                'visit_id' => $visit->id,
                'type' => ReminderType::Appointment,
                'send_at' => $validated['appointmentReminderDate'],
                'message' => "Нагадування про запис на {$visit->visit_date->format('d.m.Y H:i')}",
                'status' => ReminderStatus::Pending,
                'response_status' => ReminderResponseStatus::NoResponse,
            ]);
        }

        if ($visit->status === VisitStatus::Completed && $visit->next_service_date !== null) {
            $this->createOrUpdateRepeatServiceReminder($visit);
        }

        $this->resetCreateForm();
    }

    public function saveVisitStatus(int $visitId): void
    {
        $visit = Visit::query()->findOrFail($visitId);

        $status = $this->statusUpdates[$visitId] ?? $visit->status->value;
        $nextServiceDate = $this->nextServiceUpdates[$visitId] ?? $visit->next_service_date?->format('Y-m-d');

        validator(
            [
                'status' => $status,
                'next_service_date' => $nextServiceDate,
            ],
            [
                'status' => ['required', 'in:planned,completed,cancelled'],
                'next_service_date' => ['nullable', 'date'],
            ],
        )->validate();

        $visit->update([
            'status' => $status,
            'next_service_date' => $nextServiceDate,
        ]);

        if ($visit->status === VisitStatus::Completed && $visit->next_service_date !== null) {
            $this->createOrUpdateRepeatServiceReminder($visit);
        }
    }

    #[Computed]
    public function clients(): \Illuminate\Database\Eloquent\Collection
    {
        return Client::query()->orderBy('full_name')->get();
    }

    #[Computed]
    public function visits(): LengthAwarePaginator
    {
        return Visit::query()
            ->with('client')
            ->latest('visit_date')
            ->paginate(10);
    }

    public function render(): View
    {
        return view('livewire.visits-page');
    }

    private function createOrUpdateRepeatServiceReminder(Visit $visit): void
    {
        Reminder::query()->updateOrCreate(
            [
                'visit_id' => $visit->id,
                'type' => ReminderType::RepeatService,
            ],
            [
                'client_id' => $visit->client_id,
                'send_at' => $visit->next_service_date,
                'message' => "Нагадування про повторне ТО на {$visit->next_service_date->format('d.m.Y')}",
                'status' => ReminderStatus::Pending,
                'response_status' => ReminderResponseStatus::NoResponse,
            ],
        );
    }

    private function resetCreateForm(): void
    {
        $this->reset([
            'clientId',
            'serviceType',
            'price',
            'notes',
            'nextServiceDate',
        ]);

        $this->status = VisitStatus::Planned->value;
        $this->createAppointmentReminder = true;
        $this->visitDate = now()->addHour()->format('Y-m-d\\TH:i');
        $this->appointmentReminderDate = now()->format('Y-m-d\\TH:i');
        $this->resetValidation();
    }
}
