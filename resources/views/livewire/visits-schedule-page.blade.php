<div
    class="flex h-full w-full flex-1 flex-col gap-6"
    x-data="{
        activeDay: null,
        startTime: null,
        endTime: null,
        selecting: false,
        movingVisitId: null,
        movingDate: null,
        movingStart: null,
        resizingVisitId: null,
        resizingDate: null,
        resizingStart: null,
        resizingEnd: null,
        resizingEdge: null,
        slotMinutes: 30,
        timeToMinutes(time) {
            const [hours, minutes] = time.split(':').map(Number);
            return (hours * 60) + minutes;
        },
        minutesToTime(totalMinutes) {
            const hours = String(Math.floor(totalMinutes / 60)).padStart(2, '0');
            const minutes = String(totalMinutes % 60).padStart(2, '0');

            return `${hours}:${minutes}`;
        },
        begin(day, time) {
            this.activeDay = day;
            this.startTime = time;
            this.endTime = time;
            this.selecting = true;
        },
        hover(day, time) {
            if (!this.selecting || this.activeDay !== day) {
                return;
            }

            this.endTime = time;
        },
        selectedRange() {
            if (!this.activeDay || !this.startTime || !this.endTime) {
                return null;
            }

            const startMinutes = Math.min(this.timeToMinutes(this.startTime), this.timeToMinutes(this.endTime));
            const endMinutes = Math.max(this.timeToMinutes(this.startTime), this.timeToMinutes(this.endTime)) + this.slotMinutes;

            return {
                day: this.activeDay,
                start: this.minutesToTime(startMinutes),
                end: this.minutesToTime(endMinutes),
            };
        },
        finish() {
            if (this.movingVisitId !== null) {
                if (this.movingDate && this.movingStart) {
                    $wire.moveVisit(this.movingVisitId, this.movingDate, this.movingStart);
                }

                this.resetMove();

                return;
            }

            if (this.resizingVisitId !== null) {
                if (this.resizingStart && this.resizingEnd) {
                    const startMinutes = this.timeToMinutes(this.resizingStart);
                    const endMinutes = this.timeToMinutes(this.resizingEnd);

                    if ((endMinutes - startMinutes) >= this.slotMinutes) {
                        $wire.updateVisitTiming(this.resizingVisitId, this.resizingDate, this.resizingStart, this.resizingEnd);
                    }
                }

                this.resetResize();

                return;
            }

            if (!this.selecting) {
                return;
            }

            this.selecting = false;

            const range = this.selectedRange();

            if (range) {
                $wire.openCreateModal(range.day, range.start, range.end);
            }

            this.reset();
        },
        reset() {
            this.activeDay = null;
            this.startTime = null;
            this.endTime = null;
            this.selecting = false;
        },
        startMove(visitId, date, start) {
            this.movingVisitId = visitId;
            this.movingDate = date;
            this.movingStart = start;
        },
        updateMoveTarget(event) {
            if (this.movingVisitId === null) {
                return;
            }

            const slot = document.elementFromPoint(event.clientX, event.clientY)?.closest('[data-schedule-slot]');

            if (!slot) {
                return;
            }

            this.movingDate = slot.dataset.date;
            this.movingStart = slot.dataset.time;
        },
        resetMove() {
            this.movingVisitId = null;
            this.movingDate = null;
            this.movingStart = null;
        },
        startResize(visitId, date, start, end, edge) {
            this.resizingVisitId = visitId;
            this.resizingDate = date;
            this.resizingStart = start;
            this.resizingEnd = end;
            this.resizingEdge = edge;
        },
        hoverResize(day, time) {
            if (this.resizingVisitId === null || this.resizingDate !== day) {
                return;
            }

            if (this.resizingEdge === 'start') {
                if ((this.timeToMinutes(this.resizingEnd) - this.timeToMinutes(time)) >= this.slotMinutes) {
                    this.resizingStart = time;
                }
            }

            if (this.resizingEdge === 'end') {
                const nextTime = this.minutesToTime(this.timeToMinutes(time) + this.slotMinutes);

                if ((this.timeToMinutes(nextTime) - this.timeToMinutes(this.resizingStart)) >= this.slotMinutes) {
                    this.resizingEnd = nextTime;
                }
            }
        },
        resetResize() {
            this.resizingVisitId = null;
            this.resizingDate = null;
            this.resizingStart = null;
            this.resizingEnd = null;
            this.resizingEdge = null;
        },
        isSelected(day, time) {
            if (this.activeDay !== day || !this.startTime || !this.endTime) {
                return false;
            }

            const current = this.timeToMinutes(time);
            const start = Math.min(this.timeToMinutes(this.startTime), this.timeToMinutes(this.endTime));
            const end = Math.max(this.timeToMinutes(this.startTime), this.timeToMinutes(this.endTime));

            return current >= start && current <= end;
        }
    }"
    @mouseup.window="finish()"
    @mousemove.window="updateMoveTarget($event)"
>
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <flux:heading size="xl">Записати клієнта</flux:heading>
            <flux:text class="text-zinc-500">Оберіть часовий відрізок у потрібний день тижня та створіть запис.</flux:text>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <flux:button variant="ghost" wire:click="previousWeek" icon="chevron-left">Попередній тиждень</flux:button>
            <div class="rounded-lg border border-zinc-200 px-4 py-2 text-sm font-medium text-zinc-700">
                {{ \Carbon\CarbonImmutable::parse($this->weekStartsAt)->format('d.m') }}
                -
                {{ \Carbon\CarbonImmutable::parse($this->weekStartsAt)->addDays(6)->format('d.m.Y') }}
            </div>
            <flux:button variant="ghost" wire:click="nextWeek" icon-trailing="chevron-right">Наступний тиждень</flux:button>
        </div>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <div class="min-w-[1120px]">
                <div class="grid grid-cols-[84px_repeat(7,minmax(140px,1fr))] border-b border-zinc-200 bg-zinc-50">
                    <div class="border-e border-zinc-200 px-3 py-4 text-xs font-medium uppercase tracking-[0.2em] text-zinc-400">
                        Час
                    </div>

                    @foreach ($this->scheduleDays as $day)
                        <div class="border-e border-zinc-200 px-3 py-4 last:border-e-0">
                            <div class="text-sm font-semibold text-zinc-900">{{ $day['heading'] }}</div>
                            <div class="text-xs text-zinc-500">{{ $day['subheading'] }}</div>
                        </div>
                    @endforeach
                </div>

                <div class="grid grid-cols-[84px_repeat(7,minmax(140px,1fr))]">
                    <div class="border-e border-zinc-200 bg-zinc-50">
                        @foreach ($this->timeSlots as $slot)
                            <div class="flex h-12 items-start justify-end border-b border-zinc-200 px-3 pt-1 text-xs text-zinc-400 last:border-b-0">
                                {{ $slot['label'] }}
                            </div>
                        @endforeach
                    </div>

                    @foreach ($this->scheduleDays as $day)
                        <div class="relative border-e border-zinc-200 last:border-e-0">
                            @foreach ($this->timeSlots as $slot)
                                <button
                                    type="button"
                                    class="block h-12 w-full border-b border-zinc-200 transition-colors last:border-b-0"
                                    :class="isSelected('{{ $day['date'] }}', '{{ $slot['time'] }}') ? 'bg-zinc-100' : 'bg-white hover:bg-zinc-50'"
                                    data-schedule-slot
                                    data-date="{{ $day['date'] }}"
                                    data-time="{{ $slot['time'] }}"
                                    @mousedown.prevent="begin('{{ $day['date'] }}', '{{ $slot['time'] }}')"
                                    @mouseenter="hover('{{ $day['date'] }}', '{{ $slot['time'] }}'); hoverResize('{{ $day['date'] }}', '{{ $slot['time'] }}')"
                                    @mouseup.prevent="finish()"
                                ></button>
                            @endforeach

                            @foreach ($day['visits'] as $visit)
                                <button
                                    type="button"
                                    wire:click="openVisitDetails({{ $visit['id'] }})"
                                    @class([
                                        'group absolute inset-x-1 z-10 flex flex-col items-start rounded-xl border px-3 text-left align-top shadow-sm transition',
                                        'justify-center py-2' => $visit['compact'],
                                        'justify-start pt-3 pb-3' => ! $visit['compact'],
                                        'border-zinc-300 bg-zinc-900/6 hover:bg-zinc-900/10' => $visit['status'] === 'planned',
                                        'border-emerald-300 bg-emerald-100/90 hover:bg-emerald-100' => $visit['status'] === 'completed',
                                        'border-rose-300 bg-rose-100/90 hover:bg-rose-100' => $visit['status'] === 'cancelled',
                                    ])
                                    data-compact="{{ $visit['compact'] ? 'true' : 'false' }}"
                                    style="top: {{ $visit['top'] + 4 }}px; height: {{ $visit['height'] - 8 }}px;"
                                >
                                    <span
                                        class="absolute right-2 flex h-7 w-7 cursor-grab items-center justify-center rounded-md border border-zinc-200 bg-white/90 opacity-0 transition group-hover:opacity-100 active:cursor-grabbing"
                                        @class([
                                            'top-1.5' => $visit['compact'],
                                            'top-2' => ! $visit['compact'],
                                        ])
                                        @mousedown.prevent.stop="startMove({{ $visit['id'] }}, '{{ $visit['date'] }}', '{{ $visit['start'] }}')"
                                        @click.prevent.stop
                                    >
                                        <span class="grid grid-cols-2 gap-0.5">
                                            <span class="h-1 w-1 rounded-full bg-zinc-500"></span>
                                            <span class="h-1 w-1 rounded-full bg-zinc-500"></span>
                                            <span class="h-1 w-1 rounded-full bg-zinc-500"></span>
                                            <span class="h-1 w-1 rounded-full bg-zinc-500"></span>
                                            <span class="h-1 w-1 rounded-full bg-zinc-500"></span>
                                            <span class="h-1 w-1 rounded-full bg-zinc-500"></span>
                                        </span>
                                    </span>
                                    <span
                                        class="absolute inset-x-0 top-0 h-4 cursor-ns-resize"
                                        @mousedown.prevent.stop="startResize({{ $visit['id'] }}, '{{ $visit['date'] }}', '{{ $visit['start'] }}', '{{ $visit['end'] }}', 'start')"
                                    ></span>
                                    <div @class([
                                        'w-full truncate font-semibold text-zinc-900',
                                        'pr-8 text-xs leading-tight' => $visit['compact'],
                                        'text-sm' => ! $visit['compact'],
                                    ])>{{ $visit['client'] }}</div>
                                    @unless($visit['compact'])
                                        <div class="mt-0.5 w-full truncate text-xs text-zinc-600">{{ $visit['service'] }}</div>
                                    @endunless
                                    @unless($visit['compact'])
                                        <div class="mt-2 w-full text-xs font-medium text-zinc-500">{{ $visit['time'] }}</div>
                                    @endunless
                                    <span
                                        class="absolute inset-x-0 bottom-0 h-4 cursor-ns-resize"
                                        @mousedown.prevent.stop="startResize({{ $visit['id'] }}, '{{ $visit['date'] }}', '{{ $visit['start'] }}', '{{ $visit['end'] }}', 'end')"
                                    ></span>
                                </button>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <flux:modal wire:model="showBookingModal" class="space-y-5 md:w-[760px]">
        <div class="space-y-1">
            <flux:heading size="lg">Новий запис</flux:heading>
            <flux:text>
                {{ $selectedDate ? \Carbon\CarbonImmutable::parse($selectedDate)->format('d.m.Y') : '' }}
                @if ($selectedStartTime && $selectedEndTime)
                    {{ $selectedStartTime }} - {{ $selectedEndTime }}
                @endif
            </flux:text>
        </div>

        @if ($clientMode === 'new' && $this->matchingClientByPhone)
            <flux:callout icon="exclamation-circle" variant="secondary" class="w-full">
                <flux:callout.heading>Клієнт з таким номером уже існує</flux:callout.heading>
                <flux:callout.text>
                    {{ $this->matchingClientByPhone->full_name }}
                    @if ($this->matchingClientByPhone->car_brand || $this->matchingClientByPhone->car_model)
                        • {{ trim($this->matchingClientByPhone->car_brand . ' ' . $this->matchingClientByPhone->car_model) }}
                    @endif
                </flux:callout.text>

                <x-slot name="actions">
                    <flux:button size="sm" variant="filled" wire:click="useExistingClientFromPhoneMatch">
                        Створити візит на існуючого клієнта
                    </flux:button>
                </x-slot>
            </flux:callout>
        @endif

        <flux:radio.group wire:model.live="clientMode" variant="segmented">
            <flux:radio value="new" icon="user-plus">Новий клієнт</flux:radio>
            <flux:radio value="existing" icon="users">Існуючий клієнт</flux:radio>
        </flux:radio.group>

        @if ($clientMode === 'existing')
            <flux:field>
                <flux:label>Клієнт</flux:label>
                <flux:select wire:model="existingClientId">
                    <option value="">Оберіть клієнта</option>
                    @foreach ($this->clients as $client)
                        <option value="{{ $client->id }}">{{ $client->full_name }} ({{ $client->phone }})</option>
                    @endforeach
                </flux:select>
                <flux:error name="existingClientId" />
            </flux:field>
        @else
            <div class="grid gap-4 md:grid-cols-2">
                <flux:field>
                    <flux:label>ПІБ</flux:label>
                    <flux:input wire:model="fullName" />
                    <flux:error name="fullName" />
                </flux:field>

                <flux:field>
                    <flux:label>Телефон</flux:label>
                    <flux:input wire:model.live.debounce.300ms="phone" />
                    <flux:error name="phone" />
                </flux:field>

                <flux:field>
                    <flux:label>Марка авто</flux:label>
                    <flux:select wire:model.live="carBrand">
                        <option value="">Оберіть марку</option>
                        @foreach ($this->vehicleBrands as $brand)
                            <option value="{{ $brand }}">{{ $brand }}</option>
                        @endforeach
                        <option value="{{ $this->customBrandValue }}">Інша марка</option>
                    </flux:select>
                    <flux:error name="carBrand" />
                </flux:field>

                <flux:field>
                    <flux:label>Модель авто</flux:label>
                    <flux:select wire:model.live="carModel" wire:key="car-model-{{ $carBrand !== '' ? $carBrand : 'empty' }}">
                        @if ($this->customBrandSelected)
                            <option value="{{ $this->customModelValue }}">Вкажіть нову модель нижче</option>
                        @else
                            <option value="">{{ $carBrand === '' ? 'Спочатку оберіть марку' : 'Оберіть модель' }}</option>
                            @foreach ($this->availableModels as $model)
                                <option value="{{ $model }}">{{ $model }}</option>
                            @endforeach
                            @if ($carBrand !== '')
                                <option value="{{ $this->customModelValue }}">Інша модель</option>
                            @endif
                        @endif
                    </flux:select>
                    <flux:error name="carModel" />
                </flux:field>

                @if ($this->customBrandSelected)
                    <flux:field>
                        <flux:label>Нова марка авто</flux:label>
                        <flux:input wire:model="customCarBrand" placeholder="Наприклад, Rivian" />
                        <flux:error name="customCarBrand" />
                    </flux:field>
                @endif

                @if ($this->customModelSelected)
                    <flux:field>
                        <flux:label>Нова модель авто</flux:label>
                        <flux:input wire:model="customCarModel" placeholder="Наприклад, R1T" />
                        <flux:error name="customCarModel" />
                    </flux:field>
                @endif

                <flux:field>
                    <flux:label>Номер авто</flux:label>
                    <flux:input wire:model="carNumber" />
                    <flux:error name="carNumber" />
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
            </div>

        @endif

        <div class="grid gap-4 md:grid-cols-2">
            <flux:field>
                <flux:label>Послуга</flux:label>
                <flux:select wire:model.live="serviceType">
                    <option value="">Оберіть послугу</option>
                    @foreach ($this->serviceCatalog as $serviceName => $defaultPrice)
                        <option value="{{ $serviceName }}">
                            {{ $serviceName }}
                            @if ($defaultPrice !== null)
                                ({{ number_format((float) $defaultPrice, 0, ',', ' ') }} грн)
                            @endif
                        </option>
                    @endforeach
                    @if ($this->canManageServiceCatalog)
                        <option value="{{ $this->customServiceValue }}">Інша послуга</option>
                    @endif
                </flux:select>
                <flux:error name="serviceType" />
            </flux:field>

            <flux:field>
                <flux:label>Ціна</flux:label>
                <flux:input type="number" step="0.01" wire:model="price" />
                <flux:error name="price" />
            </flux:field>
        </div>

        @if ($this->customServiceSelected)
            <flux:field>
                <flux:label>Нова послуга</flux:label>
                <flux:input wire:model="customServiceType" placeholder="Наприклад, Заміна фільтрів" />
                <flux:error name="customServiceType" />
            </flux:field>
        @endif

        <div class="grid gap-4 md:grid-cols-2">
            <flux:field>
                <flux:label>Початок</flux:label>
                <flux:input type="time" wire:model="selectedStartTime" />
                <flux:error name="selectedStartTime" />
            </flux:field>

            <flux:field>
                <flux:label>Завершення</flux:label>
                <flux:input type="time" wire:model="selectedEndTime" />
                <flux:error name="selectedEndTime" />
            </flux:field>
        </div>

        <flux:field>
            <flux:label>Нотатки до візиту</flux:label>
            <flux:textarea wire:model="visitNotes" rows="3" />
            <flux:error name="visitNotes" />
        </flux:field>

        <flux:field>
            <flux:checkbox wire:model="cameFromReminder" label="Клієнт прийшов через нагадування" />
            <flux:error name="cameFromReminder" />
        </flux:field>

        <div class="flex justify-end gap-3">
            <flux:button variant="ghost" wire:click="closeBookingModal">Скасувати</flux:button>
            <flux:button variant="primary" wire:click="createVisit">Зберегти запис</flux:button>
        </div>
    </flux:modal>

    <flux:modal wire:model="showVisitDetailsModal" class="space-y-4 md:w-[640px]">
        @if ($this->selectedVisit)
            <div class="space-y-1">
                <flux:heading size="lg">{{ $this->selectedVisit->client->full_name }}</flux:heading>
                <flux:text>{{ $this->selectedVisit->visit_date->format('d.m.Y H:i') }} - {{ ($this->selectedVisit->visit_end_at ?? $this->selectedVisit->visit_date->copy()->addHour())->format('H:i') }}</flux:text>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4">
                    <div class="text-xs uppercase tracking-[0.18em] text-zinc-400">Телефон</div>
                    <div class="mt-2 text-sm font-medium text-zinc-900">{{ $this->selectedVisit->client->phone }}</div>
                </div>

                <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4">
                    <div class="text-xs uppercase tracking-[0.18em] text-zinc-400">Авто</div>
                    <div class="mt-2 text-sm font-medium text-zinc-900">
                        {{ $this->selectedVisit->client->car_brand }} {{ $this->selectedVisit->client->car_model }}
                        @if ($this->selectedVisit->client->car_number)
                            <span class="text-zinc-500">({{ $this->selectedVisit->client->car_number }})</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <flux:field>
                    <flux:label>Послуга</flux:label>
                    <flux:select wire:model.live="editServiceType">
                        <option value="">Оберіть послугу</option>
                        @foreach ($this->editServiceCatalog as $serviceName => $defaultPrice)
                            <option value="{{ $serviceName }}">
                                {{ $serviceName }}
                                @if ($defaultPrice !== null)
                                    ({{ number_format((float) $defaultPrice, 0, ',', ' ') }} грн)
                                @endif
                            </option>
                        @endforeach
                        @if ($this->canManageServiceCatalog)
                            <option value="{{ $this->customServiceValue }}">Інша послуга</option>
                        @endif
                    </flux:select>
                    <flux:error name="editServiceType" />
                </flux:field>

                <flux:field>
                    <flux:label>Статус</flux:label>
                    <flux:select wire:model="editStatus">
                        <option value="planned">{{ __('planned') }}</option>
                        <option value="completed">{{ __('completed') }}</option>
                        <option value="cancelled">{{ __('cancelled') }}</option>
                    </flux:select>
                    <flux:error name="editStatus" />
                </flux:field>

                <flux:field>
                    <flux:label>Початок</flux:label>
                    <flux:input type="time" wire:model="editStartTime" />
                    <flux:error name="editStartTime" />
                </flux:field>

                <flux:field>
                    <flux:label>Завершення</flux:label>
                    <flux:input type="time" wire:model="editEndTime" />
                    <flux:error name="editEndTime" />
                </flux:field>

                <flux:field>
                    <flux:label>Ціна</flux:label>
                    <flux:input type="number" step="0.01" wire:model="editPrice" />
                    <flux:error name="editPrice" />
                </flux:field>
            </div>

            @if ($this->editCustomServiceSelected)
                <flux:field>
                    <flux:label>Нова послуга</flux:label>
                    <flux:input wire:model="editCustomServiceType" placeholder="Наприклад, Комп'ютерна діагностика" />
                    <flux:error name="editCustomServiceType" />
                </flux:field>
            @endif

            <flux:field>
                <flux:label>Нотатки до візиту</flux:label>
                <flux:textarea wire:model="editVisitNotes" rows="3" />
                <flux:error name="editVisitNotes" />
            </flux:field>

            <flux:field>
                <flux:checkbox wire:model="editCameFromReminder" label="Клієнт прийшов через нагадування" />
                <flux:error name="editCameFromReminder" />
            </flux:field>

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="closeVisitDetailsModal">Закрити</flux:button>
                <flux:button variant="primary" wire:click="saveVisitDetails">Зберегти зміни</flux:button>
            </div>
        @endif
    </flux:modal>
</div>
