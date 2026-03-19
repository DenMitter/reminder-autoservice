<?php

namespace App\Livewire;

use App\Enums\ReminderResponseStatus;
use App\Enums\ReminderStatus;
use App\Enums\ReminderType;
use App\Models\Client;
use App\Models\Reminder;
use App\Models\Visit;
use App\Services\SmsService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Reminders')]
class RemindersPage extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';

    public int $clientId = 0;
    public ?int $visitId = null;
    public string $type = 'appointment';
    public string $sendAt = '';
    public string $message = '';

    public function mount(): void
    {
        $this->sendAt = now()->addHour()->format('Y-m-d\\TH:i');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function createReminder(): void
    {
        $validated = $this->validate([
            'clientId' => ['required', 'exists:clients,id'],
            'visitId' => ['nullable', 'exists:visits,id'],
            'type' => ['required', 'in:appointment,repeat_service'],
            'sendAt' => ['required', 'date'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        Reminder::query()->create([
            'client_id' => $validated['clientId'],
            'visit_id' => $validated['visitId'],
            'type' => $validated['type'],
            'send_at' => $validated['sendAt'],
            'message' => $validated['message'],
            'status' => ReminderStatus::Pending,
            'response_status' => ReminderResponseStatus::NoResponse,
        ]);

        $this->resetCreateForm();
    }

    public function sendNow(int $reminderId, SmsService $smsService): void
    {
        $reminder = Reminder::query()->findOrFail($reminderId);

        $smsService->send($reminder);
    }

    public function updateResponseStatus(int $reminderId, string $responseStatus): void
    {
        validator(
            ['response_status' => $responseStatus],
            ['response_status' => ['required', 'in:no_response,client_booked,client_came']],
        )->validate();

        Reminder::query()->findOrFail($reminderId)->update([
            'response_status' => $responseStatus,
        ]);
    }

    #[Computed]
    public function clients(): \Illuminate\Database\Eloquent\Collection
    {
        return Client::query()->orderBy('full_name')->get();
    }

    #[Computed]
    public function visits(): \Illuminate\Database\Eloquent\Collection
    {
        return Visit::query()
            ->with('client')
            ->orderByDesc('visit_date')
            ->limit(200)
            ->get();
    }

    #[Computed]
    public function reminders(): LengthAwarePaginator
    {
        return Reminder::query()
            ->with(['client', 'visit'])
            ->when(
                $this->search !== '',
                fn (Builder $query): Builder => $query->whereHas('client', function (Builder $clientQuery): Builder {
                    return $clientQuery
                        ->where('full_name', 'like', "%{$this->search}%")
                        ->orWhere('phone', 'like', "%{$this->search}%");
                }),
            )
            ->when(
                $this->statusFilter !== '',
                fn (Builder $query): Builder => $query->where('status', $this->statusFilter),
            )
            ->latest('send_at')
            ->paginate(10);
    }

    public function render(): View
    {
        return view('livewire.reminders-page');
    }

    private function resetCreateForm(): void
    {
        $this->reset(['clientId', 'visitId', 'message']);

        $this->type = ReminderType::Appointment->value;
        $this->sendAt = now()->addHour()->format('Y-m-d\\TH:i');
        $this->resetValidation();
    }
}
