<div class="flex h-full w-full flex-1 flex-col gap-6">
    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div class="w-full md:max-w-md">
            <flux:input wire:model.live.debounce.300ms="search" label="Пошук" placeholder="Назва послуги" />
        </div>

        <flux:button variant="primary" wire:click="openCreateModal">Додати послугу</flux:button>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 text-left dark:border-zinc-700">
                        <th class="pb-2">Послуга</th>
                        <th class="pb-2">Ціна за замовчуванням</th>
                        <th class="pb-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->services as $service)
                        <tr wire:key="service-{{ $service->id }}" class="border-b border-zinc-100 dark:border-zinc-800">
                            <td class="py-2">{{ $service->name }}</td>
                            <td class="py-2">
                                {{ $service->default_price !== null ? number_format((float) $service->default_price, 2, ',', ' ') . ' грн' : 'Не вказана' }}
                            </td>
                            <td class="py-2 text-right">
                                <div class="flex justify-end gap-2">
                                    <flux:button size="sm" variant="filled" wire:click="openEditModal({{ $service->id }})">Редагувати</flux:button>
                                    <flux:button size="sm" variant="ghost" wire:click="deleteService({{ $service->id }})" wire:confirm="Видалити послугу?">Видалити</flux:button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="py-4 text-zinc-500">Послуг поки немає</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $this->services->links() }}
        </div>
    </div>

    <flux:modal wire:model="showServiceModal" class="space-y-4 md:w-[560px]">
        <flux:heading>{{ $editingServiceId ? 'Редагування послуги' : 'Нова послуга' }}</flux:heading>

        <flux:field>
            <flux:label>Назва послуги</flux:label>
            <flux:input wire:model="name" />
            <flux:error name="name" />
        </flux:field>

        <flux:field>
            <flux:label>Ціна за замовчуванням</flux:label>
            <flux:input type="number" step="0.01" wire:model="defaultPrice" placeholder="Наприклад, 1200" />
            <flux:error name="defaultPrice" />
        </flux:field>

        <div class="flex justify-end gap-3">
            <flux:button variant="ghost" wire:click="closeServiceModal">Скасувати</flux:button>
            <flux:button variant="primary" wire:click="saveService">Зберегти</flux:button>
        </div>
    </flux:modal>
</div>
