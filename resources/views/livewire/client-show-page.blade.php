<div class="flex h-full w-full flex-1 flex-col gap-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">{{ $client->full_name }}</flux:heading>
        <flux:button :href="route('clients.index')" wire:navigate>До списку</flux:button>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text class="text-zinc-500">Телефон</flux:text>
            <flux:heading size="lg">{{ $client->phone }}</flux:heading>
            <flux:text class="mt-2">{{ $client->car_brand }} {{ $client->car_model }} {{ $client->car_number }}</flux:text>
            @if($client->notes)
                <flux:text class="mt-2">{{ $client->notes }}</flux:text>
            @endif
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text class="text-zinc-500">Картка клієнта</flux:text>
            <flux:text class="mt-1">Створено: {{ $client->created_at->format('d.m.Y H:i') }}</flux:text>
            <flux:text class="mt-1">Візитів: {{ $client->visits->count() }}</flux:text>
            <flux:text class="mt-1">Нагадувань: {{ $client->reminders->count() }}</flux:text>
        </div>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">Історія візитів</flux:heading>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 text-left dark:border-zinc-700">
                        <th class="pb-2">Послуга</th>
                        <th class="pb-2">Дата</th>
                        <th class="pb-2">Статус</th>
                        <th class="pb-2">Ціна</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($client->visits as $visit)
                        <tr wire:key="client-visit-{{ $visit->id }}" class="border-b border-zinc-100 dark:border-zinc-800">
                            <td class="py-2">{{ $visit->service_type }}</td>
                            <td class="py-2">{{ $visit->visit_date->format('d.m.Y H:i') }}</td>
                            <td class="py-2">{{ __($visit->status->value) }}</td>
                            <td class="py-2">{{ $visit->price }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-4 text-zinc-500">Немає візитів</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">Історія нагадувань</flux:heading>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 text-left dark:border-zinc-700">
                        <th class="pb-2">Тип</th>
                        <th class="pb-2">Дата</th>
                        <th class="pb-2">Статус</th>
                        <th class="pb-2">Відповідь</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($client->reminders as $reminder)
                        <tr wire:key="client-reminder-{{ $reminder->id }}" class="border-b border-zinc-100 dark:border-zinc-800">
                            <td class="py-2">{{ __($reminder->type->value) }}</td>
                            <td class="py-2">{{ $reminder->send_at->format('d.m.Y H:i') }}</td>
                            <td class="py-2">{{ __($reminder->status->value) }}</td>
                            <td class="py-2">{{ __($reminder->response_status->value) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-4 text-zinc-500">Немає нагадувань</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
