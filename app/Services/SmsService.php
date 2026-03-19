<?php

namespace App\Services;

use App\Enums\ReminderStatus;
use App\Models\Reminder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    public function send(Reminder $reminder): bool
    {
        $reminder->loadMissing('client');

        $phone = $this->normalizeUkrainianPhone($reminder->client?->phone);

        if ($phone === null) {
            $reminder->update([
                'status' => ReminderStatus::Failed,
            ]);

            Log::warning('SMS reminder skipped because phone is invalid.', [
                'reminder_id' => $reminder->id,
                'client_id' => $reminder->client_id,
                'phone' => $reminder->client?->phone,
            ]);

            return false;
        }

        $driver = config('sms.driver', 'log');

        $sent = match ($driver) {
            'alphasms' => $this->sendViaAlphaSms($phone, $reminder->message),
            default => $this->sendViaLog($phone, $reminder->message, $reminder->id),
        };

        if (! $sent) {
            $reminder->update([
                'status' => ReminderStatus::Failed,
            ]);

            return false;
        }

        $reminder->update([
            'status' => ReminderStatus::Sent,
            'sent_at' => now(),
        ]);

        return true;
    }

    private function sendViaLog(string $phone, string $message, int $reminderId): bool
    {
        Log::info('SMS reminder sent via log driver.', [
            'reminder_id' => $reminderId,
            'phone' => $phone,
            'message' => $message,
        ]);

        return true;
    }

    private function sendViaAlphaSms(string $phone, string $message): bool
    {
        $apiKey = config('sms.alphasms.api_key');
        $login = config('sms.alphasms.login');
        $password = config('sms.alphasms.password');
        $sender = config('sms.alphasms.sender');
        $endpoint = config('sms.alphasms.endpoint');

        if (blank($sender) || blank($endpoint)) {
            Log::warning('AlphaSMS is not configured.');

            return false;
        }

        if (filled($apiKey)) {
            $auth = $apiKey;
        } elseif (filled($login) && filled($password)) {
            $auth = sprintf('%s:%s', $login, $password);
        } else {
            Log::warning('AlphaSMS credentials are missing.');

            return false;
        }

        $payload = [
            'auth' => $auth,
            'data' => [
                [
                    'type' => 'sms',
                    'id' => (string) now()->valueOf(),
                    'phone' => ltrim($phone, '+'),
                    'sms_signature' => $sender,
                    'sms_message' => $message,
                ],
            ],
        ];

        $response = Http::timeout(15)
            ->acceptJson()
            ->withHeaders([
                'Content-Type' => 'application/json',
            ])
            ->post($endpoint, $payload);

        if ($response->failed()) {
            Log::warning('AlphaSMS request failed.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        }

        $payload = $response->json();
        $result = $payload['data'][0] ?? null;

        if (($payload['result'] ?? null) !== 'ok' && ($payload['success'] ?? false) !== true) {
            Log::warning('AlphaSMS returned an error.', [
                'response' => $payload,
            ]);

            return false;
        }

        if (($result['success'] ?? false) !== true) {
            Log::warning('AlphaSMS rejected SMS payload.', [
                'response' => $payload,
            ]);

            return false;
        }

        Log::info('AlphaSMS accepted SMS.', [
            'response' => $payload,
        ]);

        return true;
    }

    private function normalizeUkrainianPhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '380') && strlen($digits) === 12) {
            return '+'.$digits;
        }

        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            return '+38'.$digits;
        }

        return null;
    }
}
