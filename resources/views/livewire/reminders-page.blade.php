<div class="flex h-full w-full flex-1 flex-col gap-6">
    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">Нове нагадування</flux:heading>

        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <flux:field>
                <flux:label>Клієнт</flux:label>
                <flux:select wire:model="clientId">
                    <option value="">Оберіть клієнта</option>
                    @foreach($this->clients as $client)
                        <option value="{{ $client->id }}">{{ $client->full_name }} ({{ $client->phone }})</option>
                    @endforeach
                </flux:select>
                <flux:error name="clientId" />
            </flux:field>

            <flux:field>
                <flux:label>Пов'язаний візит (опціонально)</flux:label>
                <flux:select wire:model="visitId">
                    <option value="">Без візиту</option>
                    @foreach($this->visits as $visit)
                        <option value="{{ $visit->id }}">{{ $visit->client->full_name }} - {{ $visit->service_type }} ({{ $visit->visit_date->format('d.m.Y') }})</option>
                    @endforeach
                </flux:select>
                <flux:error name="visitId" />
            </flux:field>

            <flux:field>
                <flux:label>Тип</flux:label>
                <flux:select wire:model="type">
                    <option value="appointment">{{ __('appointment') }}</option>
                    <option value="repeat_service">{{ __('repeat_service') }}</option>
                </flux:select>
                <flux:error name="type" />
            </flux:field>

            <flux:field>
                <flux:label>Дата відправки</flux:label>
                <flux:input type="datetime-local" wire:model="sendAt" />
                <flux:error name="sendAt" />
            </flux:field>
        </div>

        <flux:field class="mt-4">
            <flux:label>Повідомлення</flux:label>
            <flux:textarea wire:model="message" rows="3" />
            <flux:error name="message" />
        </flux:field>

        <div class="mt-4 flex justify-end">
            <flux:button variant="primary" wire:click="createReminder">Створити reminder</flux:button>
        </div>
    </div>

    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div class="w-full md:max-w-md">
            <flux:input wire:model.live.debounce.300ms="search" label="Пошук" placeholder="Ім'я або телефон" />
        </div>
        <div class="w-full md:max-w-xs">
            <flux:select wire:model.live="statusFilter" label="Фільтр статусу">
                <option value="">Всі</option>
                <option value="pending">{{ __('pending') }}</option>
                <option value="sent">{{ __('sent') }}</option>
                <option value="failed">{{ __('failed') }}</option>
            </flux:select>
        </div>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 text-left dark:border-zinc-700">
                        <th class="pb-2">Клієнт</th>
                        <th class="pb-2">Тип</th>
                        <th class="pb-2">Дата</th>
                        <th class="pb-2">Статус</th>
                        <th class="pb-2">Відповідь клієнта</th>
                        <th class="pb-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($this->reminders as $reminder)
                        <tr wire:key="reminder-{{ $reminder->id }}" class="border-b border-zinc-100 dark:border-zinc-800">
                            <td class="py-2">{{ $reminder->client->full_name }}</td>
                            <td class="py-2">{{ __($reminder->type->value) }}</td>
                            <td class="py-2">{{ $reminder->send_at->format('d.m.Y H:i') }}</td>
                            <td class="py-2">{{ __($reminder->status->value) }}</td>
                            <td class="py-2">
                                <div class="flex flex-wrap gap-2">
                                    <flux:button size="xs" variant="ghost" wire:click="updateResponseStatus({{ $reminder->id }}, 'no_response')">{{ __('no_response') }}</flux:button>
                                    <flux:button size="xs" variant="ghost" wire:click="updateResponseStatus({{ $reminder->id }}, 'client_booked')">{{ __('client_booked') }}</flux:button>
                                    <flux:button size="xs" variant="ghost" wire:click="updateResponseStatus({{ $reminder->id }}, 'client_came')">{{ __('client_came') }}</flux:button>
                                </div>
                            </td>
                            <td class="py-2 text-right">
                                <flux:button size="sm" variant="filled" wire:click="sendNow({{ $reminder->id }})">Відправити зараз</flux:button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-4 text-zinc-500">Нагадувань немає</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $this->reminders->links() }}
        </div>
    </div>
</div>
