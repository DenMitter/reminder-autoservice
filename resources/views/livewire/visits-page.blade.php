<div class="flex h-full w-full flex-1 flex-col gap-6">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <flux:heading size="xl">Візити</flux:heading>
            <flux:text class="text-zinc-500">Створюй звичайні записи або відкрий тижневий розклад для швидкого бронювання.</flux:text>
        </div>

        <flux:button variant="primary" :href="route('visits.schedule')" wire:navigate>
            Записати клієнта
        </flux:button>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">Новий запис / візит</flux:heading>

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
                <flux:label>Послуга</flux:label>
                <flux:input wire:model="serviceType" />
                <flux:error name="serviceType" />
            </flux:field>

            <flux:field>
                <flux:label>Дата візиту</flux:label>
                <flux:input type="datetime-local" wire:model="visitDate" />
                <flux:error name="visitDate" />
            </flux:field>

            <flux:field>
                <flux:label>Ціна</flux:label>
                <flux:input type="number" step="0.01" wire:model="price" />
                <flux:error name="price" />
            </flux:field>

            <flux:field>
                <flux:label>Статус</flux:label>
                <flux:select wire:model="status">
                    <option value="planned">{{ __('planned') }}</option>
                    <option value="completed">{{ __('completed') }}</option>
                    <option value="cancelled">{{ __('cancelled') }}</option>
                </flux:select>
                <flux:error name="status" />
            </flux:field>

            <flux:field>
                <flux:label>Дата нагадування на повторне виконання послуги</flux:label>
                <flux:input type="date" wire:model="nextServiceDate" />
                <flux:error name="nextServiceDate" />
            </flux:field>
        </div>

        <flux:field class="mt-4">
            <flux:label>Нотатки</flux:label>
            <flux:textarea wire:model="notes" rows="3" />
            <flux:error name="notes" />
        </flux:field>

        <div class="mt-4 flex flex-col gap-3 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
            <flux:checkbox wire:model="createAppointmentReminder" label="Нагадати клієнту про запис" />
            <flux:field>
                <flux:label>Дата нагадування про запис</flux:label>
                <flux:input type="datetime-local" wire:model="appointmentReminderDate" />
                <flux:error name="appointmentReminderDate" />
            </flux:field>
        </div>

        <div class="mt-4 flex justify-end">
            <flux:button variant="primary" wire:click="createVisit">Створити візит</flux:button>
        </div>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">Візити</flux:heading>

        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 text-left dark:border-zinc-700">
                        <th class="pb-2">Клієнт</th>
                        <th class="pb-2">Послуга</th>
                        <th class="pb-2">Дата</th>
                        <th class="pb-2">Статус</th>
                        <th class="pb-2">Наступне ТО</th>
                        <th class="pb-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($this->visits as $visit)
                        <tr wire:key="visit-{{ $visit->id }}" class="border-b border-zinc-100 dark:border-zinc-800">
                            <td class="py-2">{{ $visit->client->full_name }}</td>
                            <td class="py-2">{{ $visit->service_type }}</td>
                            <td class="py-2">{{ $visit->visit_date->format('d.m.Y H:i') }}</td>
                            <td class="py-2">
                                <select wire:model="statusUpdates.{{ $visit->id }}" class="w-full rounded-md border-zinc-300 text-sm dark:border-zinc-700 dark:bg-zinc-800">
                                    <option value="planned" @selected(($statusUpdates[$visit->id] ?? $visit->status->value) === 'planned')>{{ __('planned') }}</option>
                                    <option value="completed" @selected(($statusUpdates[$visit->id] ?? $visit->status->value) === 'completed')>{{ __('completed') }}</option>
                                    <option value="cancelled" @selected(($statusUpdates[$visit->id] ?? $visit->status->value) === 'cancelled')>{{ __('cancelled') }}</option>
                                </select>
                            </td>
                            <td class="py-2">
                                <input
                                    type="date"
                                    wire:model="nextServiceUpdates.{{ $visit->id }}"
                                    value="{{ $nextServiceUpdates[$visit->id] ?? $visit->next_service_date?->format('Y-m-d') }}"
                                    class="w-full rounded-md border-zinc-300 text-sm dark:border-zinc-700 dark:bg-zinc-800"
                                />
                            </td>
                            <td class="py-2 text-right">
                                <flux:button size="sm" variant="filled" wire:click="saveVisitStatus({{ $visit->id }})">Зберегти</flux:button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-4 text-zinc-500">Візитів немає</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $this->visits->links() }}
        </div>
    </div>
</div>
