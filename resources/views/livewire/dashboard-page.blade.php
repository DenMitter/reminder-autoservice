<div class="flex h-full w-full flex-1 flex-col gap-6">
    <div class="flex justify-end">
        <flux:button variant="primary" :href="route('visits.schedule')" wire:navigate>
            Записати клієнта
        </flux:button>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 bg-white px-4 py-3 xl:min-h-28 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text class="text-zinc-500">Всього клієнтів</flux:text>
            <flux:heading size="xl">{{ $this->clientsTotal }}</flux:heading>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white px-4 py-3 xl:min-h-28 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text class="text-zinc-500">Візитів за період</flux:text>
            <flux:heading size="xl">{{ $this->visitsTotal }}</flux:heading>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white px-4 py-3 xl:min-h-28 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text class="text-zinc-500">Клієнтів повернулося</flux:text>
            <flux:heading size="xl">{{ $this->clientsReturned }}</flux:heading>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white px-4 py-3 xl:min-h-28 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text class="text-zinc-500">Нагадувань відправлено</flux:text>
            <flux:heading size="xl">{{ $this->remindersSent }}</flux:heading>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text class="text-zinc-500">Виручка за період</flux:text>
            <flux:heading size="lg">{{ number_format($this->revenue, 2, ',', ' ') }} грн</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text class="text-zinc-500">Записи на сьогодні</flux:text>
            <flux:heading size="lg">{{ $this->visitsToday }}</flux:heading>
        </div>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
            <div>
                <flux:heading size="lg">Графік статистики</flux:heading>
                <flux:text class="mt-2 text-zinc-500">Статистика за період: {{ $this->periodLabel }}</flux:text>
            </div>

            <div class="flex flex-col gap-3 xl:items-end">
                <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end sm:justify-end">
                    <flux:field>
                        <flux:label>Початок</flux:label>
                        <flux:input type="date" wire:model.live="startDate" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Кінець</flux:label>
                        <flux:input type="date" wire:model.live="endDate" />
                    </flux:field>

                    <div class="flex flex-wrap items-center gap-2 sm:pb-0.5">
                        <flux:button size="sm" class="bg-zinc-100 hover:bg-zinc-200" wire:click="usePeriodPreset('week')">Тиждень</flux:button>
                        <flux:button size="sm" class="bg-zinc-100 hover:bg-zinc-200" wire:click="usePeriodPreset('month')">Місяць</flux:button>
                        <flux:button size="sm" class="bg-zinc-100 hover:bg-zinc-200" wire:click="usePeriodPreset('year')">Рік</flux:button>
                    </div>
                </div>
            </div>
        </div>

        @if ($this->chart['has_data'])
            <div
                wire:key="dashboard-chart-{{ $startDate }}-{{ $endDate }}"
                x-data="dashboardLineChart(@js($this->chart))"
                class="mt-6"
            >
                <div class="h-80">
                    <canvas x-ref="canvas"></canvas>
                </div>
            </div>
        @else
            <div class="mt-6 rounded-xl border border-dashed border-zinc-200 px-4 py-10 text-center text-sm text-zinc-500 dark:border-zinc-700">
                Немає даних за обраний період.
            </div>
        @endif
    </div>
</div>
