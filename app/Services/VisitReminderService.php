<?php

namespace App\Services;

use App\Enums\ReminderResponseStatus;
use App\Enums\ReminderStatus;
use App\Enums\ReminderType;
use App\Enums\VisitStatus;
use App\Models\Reminder;
use App\Models\Visit;

class VisitReminderService
{
    public function __construct(private SmsService $smsService) {}

    public function syncAppointmentReminder(Visit $visit): void
    {
        $appointmentReminder = Reminder::query()->firstOrNew([
            'visit_id' => $visit->id,
            'type' => ReminderType::Appointment,
        ]);
        $confirmationReminder = Reminder::query()->firstOrNew([
            'visit_id' => $visit->id,
            'type' => ReminderType::AppointmentConfirmation,
        ]);

        if ($visit->status !== VisitStatus::Planned) {
            if ($appointmentReminder->exists) {
                $appointmentReminder->delete();
            }

            if ($confirmationReminder->exists && $confirmationReminder->status === ReminderStatus::Pending) {
                $confirmationReminder->delete();
            }

            return;
        }

        $sendAt = $visit->visit_date->lessThanOrEqualTo(now()->addHour())
            ? now()
            : $visit->visit_date->copy()->subHour();
        $message = $this->buildUpcomingAppointmentMessage();
        $shouldResetReminder = ! $appointmentReminder->exists
            || $appointmentReminder->client_id !== $visit->client_id
            || ! $appointmentReminder->send_at?->equalTo($sendAt)
            || $appointmentReminder->message !== $message;

        $appointmentReminder->fill([
            'client_id' => $visit->client_id,
            'send_at' => $sendAt,
            'message' => $message,
            'response_status' => ReminderResponseStatus::NoResponse,
        ]);

        if ($shouldResetReminder) {
            $appointmentReminder->status = ReminderStatus::Pending;
            $appointmentReminder->sent_at = null;
        }

        $appointmentReminder->save();

        if ($appointmentReminder->status === ReminderStatus::Pending && $appointmentReminder->send_at->lessThanOrEqualTo(now())) {
            $this->smsService->send($appointmentReminder);
        }

        $this->syncConfirmationReminder($visit, $confirmationReminder);
    }

    private function syncConfirmationReminder(Visit $visit, Reminder $confirmationReminder): void
    {
        $message = $this->buildConfirmationMessage($visit);

        $shouldResetReminder = ! $confirmationReminder->exists
            || $confirmationReminder->client_id !== $visit->client_id
            || $confirmationReminder->message !== $message;

        $confirmationReminder->fill([
            'client_id' => $visit->client_id,
            'send_at' => now(),
            'message' => $message,
            'response_status' => ReminderResponseStatus::NoResponse,
        ]);

        if ($shouldResetReminder && $confirmationReminder->status !== ReminderStatus::Sent) {
            $confirmationReminder->status = ReminderStatus::Pending;
            $confirmationReminder->sent_at = null;
        }

        $confirmationReminder->save();

        if ($confirmationReminder->status === ReminderStatus::Pending) {
            $this->smsService->send($confirmationReminder);
        }
    }

    private function buildConfirmationMessage(Visit $visit): string
    {
        $parts = [
            sprintf(
                'Ви записані на сервіс %s %s о %s',
                config('app.name'),
                $visit->visit_date->locale('uk')->isoFormat('D MMMM'),
                $visit->visit_date->format('H:i'),
            ),
        ];

        if (filled(config('app.service_address'))) {
            $parts[] = (string) config('app.service_address');
        }

        return implode("\n", $parts);
    }

    private function buildUpcomingAppointmentMessage(): string
    {
        $parts = [
            'Очікуємо Вас через 1год ;)',
        ];

        $serviceLocation = collect([
            config('app.name'),
            config('app.service_address'),
        ])->filter()->implode(', ');

        if ($serviceLocation !== '') {
            $parts[] = $serviceLocation;
        }

        if (filled(config('app.service_phone'))) {
            $parts[] = (string) config('app.service_phone');
        }

        return implode("\n", $parts);
    }
}
