<?php

namespace App\Console\Commands;

use App\Enums\ReminderStatus;
use App\Models\Reminder;
use App\Services\SmsService;
use Illuminate\Console\Command;

class ProcessPendingReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminders:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Надіслати всі заплановані нагадування, час яких настав';

    /**
     * Execute the console command.
     */
    public function handle(SmsService $smsService): int
    {
        $pendingReminders = Reminder::query()
            ->with('client')
            ->where('status', ReminderStatus::Pending)
            ->where('send_at', '<=', now())
            ->get();

        foreach ($pendingReminders as $reminder) {
            $smsService->send($reminder);
        }

        $this->info("Оброблено нагадувань: {$pendingReminders->count()}.");

        return self::SUCCESS;
    }
}
