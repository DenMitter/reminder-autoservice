<div
    class="flex h-full min-h-0 w-full flex-1 flex-col gap-6 overflow-hidden"
    x-data="{
        activeDay: null,
        startTime: null,
        endTime: null,
        selecting: false,
        movingVisitId: null,
        movingDate: null,
        movingStart: null,
        movingHeightSlots: null,
        movingClient: '',
        resizingVisitId: null,
        resizingDate: null,
        resizingStart: null,
        resizingEnd: null,
        resizingEdge: null,
        resizingClient: '',
        slotMinutes: 30,
        totalSlots: {{ count($this->timeSlots) }},
        scheduleStartTime: '{{ $this->timeSlots[0]['time'] }}',
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
        startMove(visitId, date, start, heightSlots, client) {
            this.movingVisitId = visitId;
            this.movingDate = date;
            this.movingStart = start;
            this.movingHeightSlots = heightSlots;
            this.movingClient = client;
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
            this.movingHeightSlots = null;
            this.movingClient = '';
        },
        startResize(visitId, date, start, end, edge, client) {
            this.resizingVisitId = visitId;
            this.resizingDate = date;
            this.resizingStart = start;
            this.resizingEnd = end;
            this.resizingEdge = edge;
            this.resizingClient = client;
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
            this.resizingClient = '';
        },
        isSelected(day, time) {
            if (this.activeDay !== day || !this.startTime || !this.endTime) {
                return false;
            }

            const current = this.timeToMinutes(time);
            const start = Math.min(this.timeToMinutes(this.startTime), this.timeToMinutes(this.endTime));
            const end = Math.max(this.timeToMinutes(this.startTime), this.timeToMinutes(this.endTime));

            return current >= start && current <= end;
        },
        isMoveTarget(day, time) {
            return this.movingVisitId !== null && this.movingDate === day && this.movingStart === time;
        },
        movePreviewEndTime() {
            if (!this.movingStart || this.movingHeightSlots === null) {
                return '';
            }

            return this.minutesToTime(this.timeToMinutes(this.movingStart) + (this.movingHeightSlots * this.slotMinutes));
        },
        movePreviewTime() {
            if (!this.movingStart || this.movingHeightSlots === null) {
                return '';
            }

            return `${this.movingStart} - ${this.movePreviewEndTime()}`;
        },
        movePreviewStyle() {
            if (!this.movingStart || this.movingHeightSlots === null) {
                return '';
            }

            const offsetMinutes = this.timeToMinutes(this.movingStart) - this.timeToMinutes(this.scheduleStartTime);
            const topPercent = (offsetMinutes / (this.totalSlots * this.slotMinutes)) * 100;
            const heightPercent = (this.movingHeightSlots / this.totalSlots) * 100;

            return `top: calc(${topPercent}% + 4px); height: max(calc(${heightPercent}% - 8px), 2.75rem);`;
        },
        resizePreviewTime() {
            if (!this.resizingStart || !this.resizingEnd) {
                return '';
            }

            return `${this.resizingStart} - ${this.resizingEnd}`;
        },
        resizePreviewStyle() {
            if (!this.resizingStart || !this.resizingEnd) {
                return '';
            }

            const startMinutes = this.timeToMinutes(this.resizingStart);
            const endMinutes = this.timeToMinutes(this.resizingEnd);
            const offsetMinutes = startMinutes - this.timeToMinutes(this.scheduleStartTime);
            const durationMinutes = endMinutes - startMinutes;
            const topPercent = (offsetMinutes / (this.totalSlots * this.slotMinutes)) * 100;
            const heightPercent = (durationMinutes / (this.totalSlots * this.slotMinutes)) * 100;

            return `top: calc(${topPercent}% + 4px); height: max(calc(${heightPercent}% - 8px), 2.75rem);`;
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

    <div class="flex min-h-0 flex-1 flex-col overflow-hidden rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex min-h-0 flex-1 overflow-x-auto overflow-y-hidden">
            @php($slotCount = count($this->timeSlots))

            <div class="flex h-full min-h-0 min-w-[1120px] flex-1 flex-col">
                <div class="grid shrink-0 grid-cols-[84px_repeat(7,minmax(140px,1fr))] border-b border-zinc-200 bg-zinc-50">
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

                <div class="grid min-h-0 flex-1 grid-cols-[84px_repeat(7,minmax(140px,1fr))]">
                    <div class="grid border-e border-zinc-200 bg-zinc-50" style="grid-template-rows: repeat({{ $slotCount }}, minmax(0, 1fr));">
                        @foreach ($this->timeSlots as $slot)
                            <div class="flex h-full items-start justify-end border-b border-zinc-200 px-3 pt-1 text-xs text-zinc-400 last:border-b-0">
                                {{ $slot['label'] }}
                            </div>
                        @endforeach
                    </div>

                    @foreach ($this->scheduleDays as $day)
                        <div class="relative grid border-e border-zinc-200 last:border-e-0" style="grid-template-rows: repeat({{ $slotCount }}, minmax(0, 1fr));">
                            @foreach ($this->timeSlots as $slot)
                                <button
                                    type="button"
                                    class="block h-full w-full border-b border-zinc-200 transition-colors last:border-b-0"
                                    :class="isMoveTarget('{{ $day['date'] }}', '{{ $slot['time'] }}') ? 'bg-zinc-100' : (isSelected('{{ $day['date'] }}', '{{ $slot['time'] }}') ? 'bg-zinc-100' : 'bg-zinc-50 hover:bg-zinc-100/80')"
                                    data-schedule-slot
                                    data-date="{{ $day['date'] }}"
                                    data-time="{{ $slot['time'] }}"
                                    @mousedown.prevent="begin('{{ $day['date'] }}', '{{ $slot['time'] }}')"
                                    @mouseenter="hover('{{ $day['date'] }}', '{{ $slot['time'] }}'); hoverResize('{{ $day['date'] }}', '{{ $slot['time'] }}')"
                                    @mouseup.prevent="finish()"
                                ></button>
                            @endforeach

                            <template x-if="movingVisitId !== null && movingDate === @js($day['date'])">
                                <div
                                    x-transition.opacity.duration.150ms
                                    class="pointer-events-none absolute inset-x-1 z-20 flex flex-col items-start overflow-hidden rounded-xl border border-zinc-400 bg-zinc-200 px-3 py-1.5 text-left shadow-lg ring-2 ring-zinc-300 transition-transform duration-150 ease-out"
                                    :style="movePreviewStyle()"
                                >
                                    <div class="w-full truncate text-[13px] font-semibold leading-tight text-zinc-900" x-text="movingClient"></div>
                                    <div class="mt-0.5 w-full text-xs leading-tight text-zinc-700" x-text="movePreviewTime()"></div>
                                </div>
                            </template>
                            <template x-if="resizingVisitId !== null && resizingDate === @js($day['date'])">
                                <div
                                    x-transition.opacity.duration.150ms
                                    class="pointer-events-none absolute inset-x-1 z-20 flex flex-col items-start overflow-hidden rounded-xl border border-zinc-400 bg-zinc-200 px-3 py-1.5 text-left shadow-lg ring-2 ring-zinc-300 transition-transform duration-150 ease-out"
                                    :style="resizePreviewStyle()"
                                >
                                    <div class="w-full truncate text-[13px] font-semibold leading-tight text-zinc-900" x-text="resizingClient"></div>
                                    <div class="mt-0.5 w-full text-xs leading-tight text-zinc-700" x-text="resizePreviewTime()"></div>
                                </div>
                            </template>

                            @foreach ($day['visits'] as $visit)
                                <button
                                    type="button"
                                    wire:click="openVisitDetails({{ $visit['id'] }})"
                                    @class([
                                        'group absolute inset-x-1 z-10 flex cursor-pointer flex-col items-start overflow-hidden rounded-xl border px-3 text-left align-top shadow-sm transition',
                                        'justify-start py-1.5' => $visit['compact'],
                                        'justify-start pt-3 pb-3' => ! $visit['compact'],
                                        'border-zinc-300 bg-zinc-900/6 hover:bg-zinc-900/10' => $visit['status'] === 'planned',
                                        'border-emerald-300 bg-emerald-100/90 hover:bg-emerald-100' => $visit['status'] === 'completed',
                                        'border-rose-300 bg-rose-100/90 hover:bg-rose-100' => $visit['status'] === 'cancelled',
                                    ])
                                    :class="movingVisitId === {{ $visit['id'] }} || resizingVisitId === {{ $visit['id'] }} ? 'pointer-events-none scale-[0.98] opacity-0 shadow-none' : ''"
                                    data-compact="{{ $visit['compact'] ? 'true' : 'false' }}"
                                    style="top: calc(({{ $visit['topSlots'] }} / {{ $slotCount }}) * 100% + 4px); height: max(calc(({{ $visit['heightSlots'] }} / {{ $slotCount }}) * 100% - 8px), 2.75rem);"
                                >
                                    <span
                                        class="absolute right-2 flex h-7 w-7 cursor-grab items-center justify-center rounded-md border border-zinc-200 bg-white/90 opacity-0 transition group-hover:opacity-100 active:cursor-grabbing"
                                        @class([
                                            'top-1.5' => $visit['compact'],
                                            'top-2' => ! $visit['compact'],
                                        ])
                                        @mousedown.prevent.stop="startMove({{ $visit['id'] }}, '{{ $visit['date'] }}', '{{ $visit['start'] }}', {{ $visit['heightSlots'] }}, @js($visit['client']))"
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
                                        @mousedown.prevent.stop="startResize({{ $visit['id'] }}, '{{ $visit['date'] }}', '{{ $visit['start'] }}', '{{ $visit['end'] }}', 'start', @js($visit['client']))"
                                    ></span>
                                    <div @class([
                                        'w-full font-semibold leading-tight text-zinc-900',
                                        'pr-8 whitespace-normal text-[13px]' => $visit['compact'],
                                        'truncate text-[13px]' => ! $visit['compact'],
                                    ])>{{ $visit['client'] }}</div>
                                    @if ($visit['compact'])
                                        <div class="mt-0.5 w-full text-xs leading-tight text-zinc-600">{{ $visit['time'] }}</div>
                                    @endif
                                    @if (! $visit['compact'] && $visit['showService'])
                                        <div class="mt-0.5 w-full truncate text-xs leading-tight text-zinc-600">{{ $visit['service'] }}</div>
                                    @endif
                                    @unless($visit['compact'])
                                        <div class="mt-2 w-full text-xs leading-tight font-medium text-zinc-500">{{ $visit['time'] }}</div>
                                    @endunless
                                    <span
                                        class="absolute inset-x-0 bottom-0 h-4 cursor-ns-resize"
                                        @mousedown.prevent.stop="startResize({{ $visit['id'] }}, '{{ $visit['date'] }}', '{{ $visit['start'] }}', '{{ $visit['end'] }}', 'end', @js($visit['client']))"
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
                    @if ($this->matchingClientByPhone->primaryVehicle)
                        • {{ trim($this->matchingClientByPhone->primaryVehicle->car_brand . ' ' . $this->matchingClientByPhone->primaryVehicle->car_model) }}
                    @endif
                </flux:callout.text>

                <x-slot name="actions">
                    <flux:button size="sm" variant="filled" wire:click="useExistingClientFromPhoneMatch">
                        Використати існуючого клієнта
                    </flux:button>
                </x-slot>
            </flux:callout>
        @endif

        <flux:radio.group wire:model.live="clientMode" variant="segmented">
            <flux:radio value="new" icon="user-plus">Новий клієнт</flux:radio>
            <flux:radio value="existing" icon="users">Існуючий клієнт</flux:radio>
        </flux:radio.group>

        @if ($clientMode === 'existing')
            <div class="grid gap-4 md:grid-cols-2">
                <flux:field>
                    <flux:label>Клієнт</flux:label>
                    <flux:select wire:model.live="existingClientId">
                        <option value="">Оберіть клієнта</option>
                        @foreach ($this->clients as $client)
                            <option value="{{ $client->id }}">{{ $client->full_name }} ({{ $client->phone }})</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="existingClientId" />
                </flux:field>

                @if ($this->selectedExistingClient)
                    <flux:field>
                        <flux:label>Авто</flux:label>
                        <flux:select wire:model.live="existingVehicleId">
                            @foreach ($this->existingClientVehicles as $vehicle)
                                <option value="{{ $vehicle->id }}">
                                    {{ trim($vehicle->car_brand . ' ' . $vehicle->car_model) }}
                                    @if ($vehicle->car_number)
                                        ({{ $vehicle->car_number }})
                                    @endif
                                </option>
                            @endforeach
                            <option value="{{ $this->newVehicleValue }}">Інше авто</option>
                        </flux:select>
                        <flux:error name="existingVehicleId" />
                    </flux:field>
                @endif
            </div>
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
            </div>
        @endif

        @if ($this->shouldCollectVehicleDetails)
            <div class="grid gap-4 md:grid-cols-2">
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
                    <flux:select wire:model.live="carModel" wire:key="booking-car-model-{{ $carBrand !== '' ? $carBrand : 'empty' }}">
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
            </div>
        @endif

        <div class="grid gap-4 md:grid-cols-2">
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
                <flux:label>Дата</flux:label>
                <flux:input type="date" wire:model="selectedDate" />
                <flux:error name="selectedDate" />
            </flux:field>
        </div>

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

        <flux:field>
            <flux:label>Дата нагадування на повторне виконання послуги</flux:label>
            <flux:input type="date" wire:model="nextServiceDate" />
            <flux:error name="nextServiceDate" />
        </flux:field>

        @if (filled($nextServiceDate))
            <flux:field>
                <flux:label>Шаблон нагадування</flux:label>
                <flux:textarea wire:model.live="nextServiceReminderMessage" rows="3" />
                <flux:error name="nextServiceReminderMessage" />
            </flux:field>
        @endif

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

    @if (false)
    <flux:modal wire:model="showBookingModal" class="overflow-hidden p-0 [:where(&)]:min-w-0 [:where(&)]:w-[calc(100vw-2rem)] [:where(&)]:max-w-none">
        <div class="flex h-[88dvh] max-h-[88dvh] flex-col bg-white">
            <div class="border-b border-zinc-200 px-5 py-4">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                    <div class="space-y-1">
                        <flux:heading size="xl">Додати запис</flux:heading>
                        <flux:text class="text-sm text-zinc-500">
                            {{ $selectedDate ? \Carbon\CarbonImmutable::parse($selectedDate)->format('d.m.Y') : '' }}
                            @if ($selectedStartTime && $selectedEndTime)
                                • {{ $selectedStartTime }} - {{ $selectedEndTime }}
                            @endif
                        </flux:text>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <flux:radio.group wire:model.live="clientMode" variant="segmented">
                            <flux:radio value="new" icon="user-plus">Новий клієнт</flux:radio>
                            <flux:radio value="existing" icon="users">Існуючий клієнт</flux:radio>
                        </flux:radio.group>
                    </div>
                </div>
            </div>

            <div class="flex-1 overflow-hidden bg-zinc-50 px-5 py-4">
                <div class="space-y-4">
                    @if ($clientMode === 'new' && $this->matchingClientByPhone)
                        <flux:callout icon="exclamation-circle" variant="secondary" class="w-full">
                            <flux:callout.heading>Клієнт з таким номером уже існує</flux:callout.heading>
                            <flux:callout.text>
                                {{ $this->matchingClientByPhone->full_name }}
                                @if ($this->matchingClientByPhone->primaryVehicle)
                                    • {{ trim($this->matchingClientByPhone->primaryVehicle->car_brand . ' ' . $this->matchingClientByPhone->primaryVehicle->car_model) }}
                                @endif
                            </flux:callout.text>

                            <x-slot name="actions">
                                <flux:button size="sm" variant="filled" wire:click="useExistingClientFromPhoneMatch">
                                    Використати існуючого клієнта
                                </flux:button>
                            </x-slot>
                        </flux:callout>
                    @endif

                    <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_300px]">
                        <div class="grid content-start gap-4 md:grid-cols-2">
                            <section class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm md:col-span-2">
                                <div class="mb-4">
                                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-zinc-400">Запис</div>
                                    <div class="mt-1 text-sm font-medium text-zinc-900">Час і параметри візиту</div>
                                </div>

                                <div class="grid gap-3 md:grid-cols-2">
                                    <flux:field>
                                        <flux:label>Дата</flux:label>
                                        <flux:input type="date" wire:model="selectedDate" />
                                        <flux:error name="selectedDate" />
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
                            </section>

                            <section class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
                                <div class="mb-4">
                                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-zinc-400">Клієнт</div>
                                    <div class="mt-1 text-sm font-medium text-zinc-900">Дані власника авто</div>
                                </div>

                                @if ($clientMode === 'existing')
                                    <div class="grid gap-3 md:grid-cols-2">
                                        <flux:field>
                                            <flux:label>Клієнт</flux:label>
                                            <flux:select wire:model.live="existingClientId">
                                                <option value="">Оберіть клієнта</option>
                                                @foreach ($this->clients as $client)
                                                    <option value="{{ $client->id }}">{{ $client->full_name }} ({{ $client->phone }})</option>
                                                @endforeach
                                            </flux:select>
                                            <flux:error name="existingClientId" />
                                        </flux:field>

                                        @if ($this->selectedExistingClient)
                                            <flux:field>
                                                <flux:label>Авто</flux:label>
                                                <flux:select wire:model.live="existingVehicleId">
                                                    @foreach ($this->existingClientVehicles as $vehicle)
                                                        <option value="{{ $vehicle->id }}">
                                                            {{ trim($vehicle->car_brand . ' ' . $vehicle->car_model) }}
                                                            @if ($vehicle->car_number)
                                                                ({{ $vehicle->car_number }})
                                                            @endif
                                                        </option>
                                                    @endforeach
                                                    <option value="{{ $this->newVehicleValue }}">Інше авто</option>
                                                </flux:select>
                                                <flux:error name="existingVehicleId" />
                                            </flux:field>
                                        @endif
                                    </div>
                                @else
                                    <div class="grid gap-3 md:grid-cols-2">
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
                                    </div>
                                @endif
                            </section>

                            @if ($this->shouldCollectVehicleDetails)
                                <section class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
                                    <div class="mb-4">
                                        <div class="text-xs font-semibold uppercase tracking-[0.18em] text-zinc-400">Автомобіль</div>
                                        <div class="mt-1 text-sm font-medium text-zinc-900">Вибір або додавання авто</div>
                                    </div>

                                    <div class="grid gap-3 md:grid-cols-2">
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
                                    </div>
                                </section>
                            @endif

                            <section class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm md:col-span-2">
                                <div class="mb-4">
                                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-zinc-400">Послуга</div>
                                    <div class="mt-1 text-sm font-medium text-zinc-900">Що робимо і за яку ціну</div>
                                </div>

                                <div class="grid gap-3 md:grid-cols-2">
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
                                    <div class="mt-3">
                                        <flux:field>
                                            <flux:label>Нова послуга</flux:label>
                                            <flux:input wire:model="customServiceType" placeholder="Наприклад, Заміна фільтрів" />
                                            <flux:error name="customServiceType" />
                                        </flux:field>
                                    </div>
                                @endif
                            </section>
                        </div>

                        <aside>
                            <section class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
                                <div class="mb-4">
                                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-zinc-400">Додатково</div>
                                    <div class="mt-1 text-sm font-medium text-zinc-900">Примітки й джерело запису</div>
                                </div>

                                <div class="space-y-3">
                                    <flux:field>
                                        <flux:checkbox wire:model="cameFromReminder" label="Клієнт прийшов через нагадування" />
                                        <flux:error name="cameFromReminder" />
                                    </flux:field>

                                    <flux:field>
                                        <flux:label>Нотатки до візиту</flux:label>
                                        <flux:textarea wire:model="visitNotes" rows="5" />
                                        <flux:error name="visitNotes" />
                                    </flux:field>
                                </div>
                            </section>
                        </aside>
                    </div>
                </div>
            </div>

            <div class="border-t border-zinc-200 bg-white px-5 py-3">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="text-sm text-zinc-500">
                        Перевір дані клієнта, авто і часовий слот перед збереженням.
                    </div>

                    <div class="flex justify-end gap-3">
                        <flux:button variant="ghost" wire:click="closeBookingModal">Скасувати</flux:button>
                        <flux:button variant="primary" wire:click="createVisit">Зберегти запис</flux:button>
                    </div>
                </div>
            </div>
        </div>
    </flux:modal>
    @endif

    <flux:modal wire:model="showVisitDetailsModal" class="space-y-4 md:w-[640px]">
        @if ($this->selectedVisit)
            @php($selectedVehicle = $this->selectedVisit->clientVehicle ?? $this->selectedVisit->client->primaryVehicle)
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
                        @if ($selectedVehicle)
                            {{ $selectedVehicle->car_brand }} {{ $selectedVehicle->car_model }}
                            @if ($selectedVehicle->car_number)
                                <span class="text-zinc-500">({{ $selectedVehicle->car_number }})</span>
                            @endif
                        @else
                            —
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
                <flux:label>Дата нагадування на повторне виконання послуги</flux:label>
                <flux:input type="date" wire:model="editNextServiceDate" />
                <flux:error name="editNextServiceDate" />
            </flux:field>

            @if (filled($editNextServiceDate))
                <flux:field>
                    <flux:label>Шаблон нагадування</flux:label>
                    <flux:textarea wire:model.live="editNextServiceReminderMessage" rows="3" />
                    <flux:error name="editNextServiceReminderMessage" />
                </flux:field>
            @endif

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
