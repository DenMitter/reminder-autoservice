<div class="flex h-full w-full flex-1 flex-col gap-6">
    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div class="w-full md:max-w-md">
            <flux:input wire:model.live.debounce.300ms="search" label="Пошук" placeholder="Ім'я, телефон, авто" />
        </div>
        <flux:button variant="primary" wire:click="openCreateModal">Додати клієнта</flux:button>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 text-left dark:border-zinc-700">
                        <th class="pb-2">Ім'я</th>
                        <th class="pb-2">Телефон</th>
                        <th class="pb-2">Авто</th>
                        <th class="pb-2">Візитів</th>
                        <th class="pb-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($this->clients as $client)
                        <tr wire:key="client-{{ $client->id }}" class="border-b border-zinc-100 dark:border-zinc-800">
                            <td class="py-2">{{ $client->full_name }}</td>
                            <td class="py-2">{{ $client->phone }}</td>
                            <td class="py-2">{{ $client->car_brand }} {{ $client->car_model }}</td>
                            <td class="py-2">{{ $client->visits_count }}</td>
                            <td class="py-2 text-right">
                                <div class="flex justify-end gap-2">
                                    <flux:button size="sm" variant="filled" wire:click="openEditModal({{ $client->id }})">Редагувати</flux:button>
                                    <flux:button size="sm" :href="route('clients.show', $client)" wire:navigate>Відкрити</flux:button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-4 text-zinc-500">Клієнтів не знайдено</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $this->clients->links() }}
        </div>
    </div>

    <flux:modal wire:model="showClientModal" class="space-y-4 md:w-[620px]">
        <flux:heading>{{ $editingClientId ? 'Редагування клієнта' : 'Новий клієнт' }}</flux:heading>

        <flux:field>
            <flux:label>ПІБ</flux:label>
            <flux:input wire:model="fullName" />
            <flux:error name="fullName" />
        </flux:field>

        <flux:field>
            <flux:label>Телефон</flux:label>
            <flux:input wire:model="phone" />
            <flux:error name="phone" />
        </flux:field>

        <div class="grid gap-4 md:grid-cols-2">
            <flux:field>
                <flux:label>Марка авто</flux:label>
                <flux:input wire:model="carBrand" />
                <flux:error name="carBrand" />
            </flux:field>

            <flux:field>
                <flux:label>Модель авто</flux:label>
                <flux:input wire:model="carModel" />
                <flux:error name="carModel" />
            </flux:field>
        </div>

        <flux:field>
            <flux:label>Номер авто</flux:label>
            <flux:input wire:model="carNumber" />
            <flux:error name="carNumber" />
        </flux:field>

        <flux:field>
            <flux:label>Нотатки</flux:label>
            <flux:textarea wire:model="notes" rows="3" />
            <flux:error name="notes" />
        </flux:field>

        <div class="flex justify-end">
            <flux:button variant="primary" wire:click="saveClient">Зберегти</flux:button>
        </div>
    </flux:modal>
</div>
