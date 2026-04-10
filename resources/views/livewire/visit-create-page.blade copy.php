<style>
    .visit-create-section {
        border-radius: 32px;
    }

    .visit-create-fields {
        gap: 1.75rem;
    }

    @media (min-width: 1280px) {
        .visit-create-grid {
            grid-template-columns: minmax(0, 1.1fr) minmax(0, 1.1fr) minmax(320px, 0.82fr);
            grid-template-areas:
                "booking booking summary"
                "client vehicle summary"
                "service service notes";
            align-items: start;
        }

        .visit-create-booking {
            grid-area: booking;
        }

        .visit-create-summary {
            grid-area: summary;
        }

        .visit-create-client {
            grid-area: client;
        }

        .visit-create-vehicle {
            grid-area: vehicle;
        }

        .visit-create-service {
            grid-area: service;
        }

        .visit-create-notes {
            grid-area: notes;
        }
    }
</style>

@once
    <script>
        window.syncVisitServicePrice ??= function (selectElement) {
            const serviceSection = selectElement.closest('[data-visit-service]');

            if (! serviceSection) {
                return;
            }

            const priceInput = serviceSection.querySelector('[data-price-input]');

            if (! priceInput) {
                return;
            }

            const nextPrice = selectElement.selectedOptions[0]?.dataset.price ?? '';

            priceInput.value = nextPrice;
            priceInput.dispatchEvent(new Event('input', { bubbles: true }));
            priceInput.dispatchEvent(new Event('change', { bubbles: true }));
        };
    </script>
@endonce

<div class="flex h-full min-h-0 w-full flex-1 flex-col overflow-hidden bg-zinc-50">
    <div class="border-b border-zinc-200 bg-white">
        <div class="mx-auto flex w-full max-w-[1720px] flex-col gap-4 px-6 py-5 lg:px-8 xl:flex-row xl:items-start xl:justify-between xl:px-10 2xl:px-12">
            <div class="space-y-1.5">
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

                <flux:button variant="ghost" :href="route('visits.schedule')" wire:navigate>Назад до розкладу</flux:button>
            </div>
        </div>
    </div>

    <div class="flex-1 overflow-y-auto">
        <div class="mx-auto w-full max-w-[1720px] space-y-8 px-6 py-6 lg:px-8 xl:px-10 xl:py-8 2xl:px-12">
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

            <!-- <div class="visit-create-grid grid gap-8"> -->
                    <section class="visit-create-booking visit-create-section border border-zinc-200 bg-white p-8 shadow-sm lg:p-9 xl:order-1 xl:col-span-8">
                        <div class="mb-4">
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-zinc-400">Запис</div>
                            <div class="mt-1 text-sm font-medium text-zinc-900">Час і параметри візиту</div>
                        </div>

                        <div class="visit-create-fields grid md:grid-cols-2" x-data="{}">
                            <flux:field>
                                <flux:label>Дата</flux:label>
                                <flux:input type="date" wire:model="selectedDate" :value="$selectedDate" />
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
                                <flux:input type="time" wire:model="selectedStartTime" :value="$selectedStartTime" />
                                <flux:error name="selectedStartTime" />
                            </flux:field>

                            <flux:field>
                                <flux:label>Завершення</flux:label>
                                <flux:input type="time" wire:model="selectedEndTime" :value="$selectedEndTime" />
                                <flux:error name="selectedEndTime" />
                            </flux:field>
                        </div>
                    </section>

                    <section class="visit-create-client visit-create-section border border-zinc-200 bg-white p-8 shadow-sm lg:p-9 xl:order-3 xl:col-span-6">
                        <div class="mb-4">
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-zinc-400">Клієнт</div>
                            <div class="mt-1 text-sm font-medium text-zinc-900">Дані власника авто</div>
                        </div>

                        @if ($clientMode === 'existing')
                            <div class="visit-create-fields grid md:grid-cols-2">
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
                            <div class="visit-create-fields grid md:grid-cols-2">
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
                        <section class="visit-create-vehicle visit-create-section border border-zinc-200 bg-white p-8 shadow-sm lg:p-9 xl:order-4 xl:col-span-6">
                            <div class="mb-4">
                                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-zinc-400">Автомобіль</div>
                                <div class="mt-1 text-sm font-medium text-zinc-900">Вибір або додавання авто</div>
                            </div>

                            <div class="visit-create-fields grid md:grid-cols-2">
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

                    <section class="visit-create-service visit-create-section border border-zinc-200 bg-white p-8 shadow-sm lg:p-9 xl:order-5 xl:col-span-7" data-visit-service>
                        <div class="mb-4">
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-zinc-400">Послуга</div>
                            <div class="mt-1 text-sm font-medium text-zinc-900">Що робимо і за яку ціну</div>
                        </div>

                        <div class="visit-create-fields grid md:grid-cols-2">
                            <flux:field>
                                <flux:label>Послуга</flux:label>
                                <select
                                    wire:model.change="serviceType"
                                    onchange="window.syncVisitServicePrice(this)"
                                    class="block w-full rounded-xl border border-zinc-200 bg-white px-4 py-3 text-base text-zinc-900 shadow-xs outline-none transition focus:border-zinc-300 focus:ring-2 focus:ring-zinc-200"
                                >
                                    <option value="">Оберіть послугу</option>
                                    @foreach ($this->serviceCatalog as $serviceName => $defaultPrice)
                                        <option value="{{ $serviceName }}" data-price="{{ $defaultPrice ?? '' }}">
                                            {{ $serviceName }}
                                            @if ($defaultPrice !== null)
                                                ({{ number_format((float) $defaultPrice, 0, ',', ' ') }} грн)
                                            @endif
                                        </option>
                                    @endforeach
                                    @if ($this->canManageServiceCatalog)
                                        <option value="{{ $this->customServiceValue }}">Інша послуга</option>
                                    @endif
                                </select>
                                <flux:error name="serviceType" />
                            </flux:field>

                            <flux:field wire:key="create-price-field-{{ $serviceType !== '' ? md5($serviceType) : 'empty' }}-{{ $price ?? 'null' }}">
                                <flux:label>Ціна</flux:label>
                                <input
                                    type="number"
                                    step="0.01"
                                    wire:model.live="price"
                                    data-price-input
                                    value="{{ $price ?? '' }}"
                                    class="block w-full rounded-xl border border-zinc-200 bg-white px-4 py-3 text-base text-zinc-900 shadow-xs outline-none transition focus:border-zinc-300 focus:ring-2 focus:ring-zinc-200"
                                />
                                <flux:error name="price" />
                            </flux:field>
                        </div>

                        @if ($this->customServiceSelected)
                            <div class="mt-5">
                                <flux:field>
                                    <flux:label>Нова послуга</flux:label>
                                    <flux:input wire:model="customServiceType" placeholder="Наприклад, Заміна фільтрів" />
                                    <flux:error name="customServiceType" />
                                </flux:field>
                            </div>
                        @endif
                    </section>
                
                    <section class="visit-create-summary visit-create-section border border-amber-200 bg-amber-50 p-8 shadow-sm lg:p-9 xl:order-2 xl:col-span-4">
                        @php($summaryAmount = filled($price) ? number_format((float) $price, 0, ',', ' ') : '0')

                        <div class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-700/70">Підсумок</div>
                        <div class="mt-5 grid gap-x-5 gap-y-4 sm:grid-cols-[minmax(0,1fr)_minmax(220px,1.1fr)]">
                            <div class="space-y-4">
                                <div class="text-[1.05rem] font-semibold text-zinc-800">Сума {{ $summaryAmount }} грн.</div>
                                <div class="text-[1.05rem] font-semibold text-zinc-800">Сплачено 0 грн.</div>
                            </div>

                            <div class="rounded-[24px] border border-amber-200 bg-amber-100 px-6 py-7 text-center text-[2rem] font-semibold leading-none text-amber-950">
                                Залишок {{ $summaryAmount }} грн.
                            </div>

                            <div class="self-center text-base font-medium text-zinc-700">Спосіб розрахунку</div>
                            <flux:select wire:model="paymentMethod">
                                <option value="">Виберіть спосіб розрахунку</option>
                                <option value="cash">Готівка</option>
                                <option value="card">Картка</option>
                                <option value="transfer">Переказ</option>
                            </flux:select>

                            <div class="self-center text-base font-medium text-zinc-700">Реквізити СТО</div>
                            <flux:select wire:model="serviceStationRequisites">
                                <option value="">Вкажіть реквізити</option>
                                <option value="main-terminal">Основний термінал</option>
                                <option value="cash-desk">Каса СТО</option>
                                <option value="invoice">Рахунок-фактура</option>
                            </flux:select>

                            <div class="self-center text-base font-medium text-zinc-700">
                                <span class="text-rose-500">*</span> Час видачі
                            </div>
                            <flux:input type="time" wire:model.live="selectedEndTime" :value="$selectedEndTime" />
                        </div>
                    </section>

                    <section class="visit-create-notes visit-create-section border border-zinc-200 bg-white p-8 shadow-sm lg:p-9 xl:order-6 xl:col-span-5">
                        <div class="mb-4">
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-zinc-400">Додатково</div>
                            <div class="mt-1 text-sm font-medium text-zinc-900">Примітки й джерело запису</div>
                        </div>

                        <div class="space-y-5">
                            <flux:field>
                                <flux:checkbox wire:model="cameFromReminder" label="Клієнт прийшов через нагадування" />
                                <flux:error name="cameFromReminder" />
                            </flux:field>

                            <flux:field>
                                <flux:label>Нотатки до візиту</flux:label>
                                <flux:textarea wire:model="visitNotes" rows="11" />
                                <flux:error name="visitNotes" />
                            </flux:field>
                        </div>
                    </section>
                
            </div>
        <!-- </div> -->
    </div>

    <div class="border-t border-zinc-200 bg-white">
        <div class="mx-auto flex w-full max-w-[1720px] flex-col gap-3 px-6 py-4 lg:px-8 sm:flex-row sm:items-center sm:justify-between xl:px-10 2xl:px-12">
            <div class="text-sm text-zinc-500">
                Перевір дані клієнта, авто і часовий слот перед збереженням.
            </div>

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" :href="route('visits.schedule')" wire:navigate>Скасувати</flux:button>
                <flux:button variant="primary" wire:click="createVisit">Зберегти запис</flux:button>
            </div>
        </div>
    </div>
</div>
