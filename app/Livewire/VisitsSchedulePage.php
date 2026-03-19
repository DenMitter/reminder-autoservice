<?php

namespace App\Livewire;

use App\Enums\VisitStatus;
use App\Models\Client;
use App\Models\ServiceCatalogItem;
use App\Models\Visit;
use App\Services\VisitReminderService;
use App\Services\VehicleCatalogService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Запис клієнта')]
class VisitsSchedulePage extends Component
{
    private const SCHEDULE_START_HOUR = 8;
    private const SCHEDULE_END_HOUR = 20;
    private const SLOT_MINUTES = 30;
    private const SLOT_HEIGHT = 48;
    private const CUSTOM_BRAND = '__custom_brand__';
    private const CUSTOM_MODEL = '__custom_model__';
    private const CUSTOM_SERVICE = '__custom_service__';

    public string $weekStartsAt = '';

    public bool $showBookingModal = false;

    public bool $showVisitDetailsModal = false;

    public ?int $selectedVisitId = null;

    public string $selectedDate = '';

    public string $selectedStartTime = '';

    public string $selectedEndTime = '';

    public string $clientMode = 'new';

    public ?int $existingClientId = null;

    public string $fullName = '';

    public string $phone = '';

    public string $carBrand = '';

    public string $carModel = '';

    public ?string $customCarBrand = null;

    public ?string $customCarModel = null;

    public ?string $carNumber = null;

    public string $serviceType = '';

    public ?string $customServiceType = null;

    public ?string $price = null;

    public string $status = 'planned';

    public bool $cameFromReminder = false;

    public ?string $visitNotes = null;

    public string $editServiceType = '';

    public ?string $editCustomServiceType = null;

    public string $editStatus = 'planned';

    public bool $editCameFromReminder = false;

    public ?string $editPrice = null;

    public string $editStartTime = '';

    public string $editEndTime = '';

    public ?string $editVisitNotes = null;

    public function mount(): void
    {
        $this->weekStartsAt = now()->startOfWeek()->toDateString();
        $this->status = VisitStatus::Planned->value;
    }

    public function previousWeek(): void
    {
        $this->weekStartsAt = CarbonImmutable::parse($this->weekStartsAt)
            ->subWeek()
            ->toDateString();
    }

    public function nextWeek(): void
    {
        $this->weekStartsAt = CarbonImmutable::parse($this->weekStartsAt)
            ->addWeek()
            ->toDateString();
    }

    public function openCreateModal(string $date, string $startTime, string $endTime): void
    {
        $this->resetValidation();

        $this->selectedDate = $date;
        $this->selectedStartTime = $startTime;
        $this->selectedEndTime = $endTime;
        $this->serviceType = '';
        $this->customServiceType = null;
        $this->price = null;
        $this->cameFromReminder = false;
        $this->visitNotes = null;
        $this->existingClientId = null;
        $this->fullName = '';
        $this->phone = '';
        $this->carBrand = '';
        $this->carModel = '';
        $this->customCarBrand = null;
        $this->customCarModel = null;
        $this->carNumber = null;
        $this->clientMode = 'new';
        $this->showBookingModal = true;
    }

    public function updatedCarBrand(string $value): void
    {
        if ($value !== self::CUSTOM_BRAND) {
            $this->customCarBrand = null;
        }

        if ($value === self::CUSTOM_BRAND) {
            $this->carModel = self::CUSTOM_MODEL;

            return;
        }

        if (! in_array($this->carModel, $this->availableModels, true)) {
            $this->carModel = '';
        }

        if ($value === '') {
            $this->carModel = '';
        }
    }

    public function updatedCarModel(string $value): void
    {
        if ($value !== self::CUSTOM_MODEL) {
            $this->customCarModel = null;
        }
    }

    public function useExistingClientFromPhoneMatch(): void
    {
        $matchingClient = $this->matchingClientByPhone;

        if ($matchingClient === null) {
            return;
        }

        $this->clientMode = 'existing';
        $this->existingClientId = $matchingClient->id;
        $this->fullName = '';
        $this->phone = '';
        $this->carBrand = '';
        $this->carModel = '';
        $this->customCarBrand = null;
        $this->customCarModel = null;
        $this->carNumber = null;
        $this->resetValidation();
    }

    public function updatedServiceType(string $value): void
    {
        if ($value !== self::CUSTOM_SERVICE) {
            $this->customServiceType = null;
        }

        if ($value === '' || $value === self::CUSTOM_SERVICE) {
            $this->price = null;

            return;
        }

        $defaultPrice = $this->defaultPriceForService($value);

        if ($defaultPrice !== null) {
            $this->price = $defaultPrice;
        }
    }

    public function updatedEditServiceType(string $value): void
    {
        if ($value !== self::CUSTOM_SERVICE) {
            $this->editCustomServiceType = null;
        }

        if ($value === '' || $value === self::CUSTOM_SERVICE) {
            $this->editPrice = null;

            return;
        }

        $defaultPrice = $this->defaultPriceForService($value);

        if ($defaultPrice !== null) {
            $this->editPrice = $defaultPrice;
        }
    }

    public function openVisitDetails(int $visitId): void
    {
        $visit = Visit::query()
            ->with('client')
            ->findOrFail($visitId);

        $visitEnd = $visit->visit_end_at ?? $visit->visit_date->copy()->addHour();

        $this->selectedVisitId = $visitId;
        $this->editServiceType = $visit->service_type;
        $this->editCustomServiceType = null;
        $this->editStatus = $visit->status->value;
        $this->editCameFromReminder = $visit->came_from_reminder;
        $this->editPrice = $visit->price !== null ? (string) $visit->price : null;
        $this->editStartTime = $visit->visit_date->format('H:i');
        $this->editEndTime = $visitEnd->format('H:i');
        $this->editVisitNotes = $visit->notes;
        $this->showVisitDetailsModal = true;
        $this->resetValidation();
    }

    public function closeVisitDetailsModal(): void
    {
        $this->reset([
            'selectedVisitId',
            'showVisitDetailsModal',
            'editServiceType',
            'editCustomServiceType',
            'editStatus',
            'editCameFromReminder',
            'editPrice',
            'editStartTime',
            'editEndTime',
            'editVisitNotes',
        ]);
    }

    public function updateVisitTiming(int $visitId, string $date, string $startTime, string $endTime): void
    {
        $visitStart = CarbonImmutable::createFromFormat('Y-m-d H:i', "{$date} {$startTime}");
        $visitEnd = CarbonImmutable::createFromFormat('Y-m-d H:i', "{$date} {$endTime}");

        if ($visitEnd->lessThanOrEqualTo($visitStart)) {
            return;
        }

        $visit = Visit::query()->findOrFail($visitId);

        $visit->update([
            'visit_date' => $visitStart,
            'visit_end_at' => $visitEnd,
        ]);

        app(VisitReminderService::class)->syncAppointmentReminder($visit->fresh());

        if ($this->selectedVisitId === $visitId) {
            $this->editStartTime = $visitStart->format('H:i');
            $this->editEndTime = $visitEnd->format('H:i');
        }
    }

    public function moveVisit(int $visitId, string $date, string $startTime): void
    {
        $visit = Visit::query()->findOrFail($visitId);
        $currentEnd = $visit->visit_end_at ?? $visit->visit_date->copy()->addHour();
        $durationInMinutes = $visit->visit_date->diffInMinutes($currentEnd);

        $visitStart = CarbonImmutable::createFromFormat('Y-m-d H:i', "{$date} {$startTime}");
        $visitEnd = $visitStart->addMinutes($durationInMinutes);

        $visit->update([
            'visit_date' => $visitStart,
            'visit_end_at' => $visitEnd,
        ]);

        app(VisitReminderService::class)->syncAppointmentReminder($visit->fresh());

        if ($this->selectedVisitId === $visitId) {
            $this->editStartTime = $visitStart->format('H:i');
            $this->editEndTime = $visitEnd->format('H:i');
        }
    }

    public function saveVisitDetails(): void
    {
        if ($this->selectedVisitId === null) {
            return;
        }

        $visit = Visit::query()
            ->with('client')
            ->findOrFail($this->selectedVisitId);

        $validated = $this->validate([
            'editServiceType' => ['required', 'string', 'max:255'],
            'editCustomServiceType' => ['nullable', 'string', 'max:255'],
            'editStatus' => ['required', 'in:planned,completed,cancelled'],
            'editCameFromReminder' => ['boolean'],
            'editPrice' => ['nullable', 'numeric', 'min:0'],
            'editStartTime' => ['required', 'date_format:H:i'],
            'editEndTime' => ['required', 'date_format:H:i'],
            'editVisitNotes' => ['nullable', 'string', 'max:5000'],
        ]);

        $visitStart = CarbonImmutable::createFromFormat(
            'Y-m-d H:i',
            "{$visit->visit_date->toDateString()} {$validated['editStartTime']}",
        );
        $visitEnd = CarbonImmutable::createFromFormat(
            'Y-m-d H:i',
            "{$visit->visit_date->toDateString()} {$validated['editEndTime']}",
        );

        if ($visitEnd->lessThanOrEqualTo($visitStart)) {
            $this->addError('editEndTime', 'Час завершення має бути пізніше за початок.');

            return;
        }

        $this->validateServiceSelection(editing: true);
        $serviceType = $this->resolvedServiceType(editing: true);
        $this->storeCustomServiceIfNeeded($validated['editPrice'], editing: true);

        $visit->update([
            'service_type' => $serviceType,
            'status' => $validated['editStatus'],
            'came_from_reminder' => (bool) $validated['editCameFromReminder'],
            'price' => $validated['editPrice'],
            'visit_date' => $visitStart,
            'visit_end_at' => $visitEnd,
            'notes' => $validated['editVisitNotes'],
        ]);

        app(VisitReminderService::class)->syncAppointmentReminder($visit->fresh());

        $this->openVisitDetails($visit->id);
    }

    public function createVisit(): void
    {
        $validated = $this->validate($this->rules());
        $this->validateVehicleSelection();
        $this->validateServiceSelection();

        $visitStart = CarbonImmutable::createFromFormat(
            'Y-m-d H:i',
            "{$validated['selectedDate']} {$validated['selectedStartTime']}",
        );

        $visitEnd = CarbonImmutable::createFromFormat(
            'Y-m-d H:i',
            "{$validated['selectedDate']} {$validated['selectedEndTime']}",
        );

        if ($visitEnd->lessThanOrEqualTo($visitStart)) {
            $this->addError('selectedEndTime', 'Час завершення має бути пізніше за початок.');

            return;
        }

        $serviceType = $this->resolvedServiceType();
        $this->storeCustomServiceIfNeeded($validated['price']);

        $client = $this->clientMode === 'existing'
            ? Client::query()->findOrFail($validated['existingClientId'])
            : Client::query()->create([
                'full_name' => $validated['fullName'],
                'phone' => $validated['phone'],
                'car_brand' => $this->resolvedCarBrand(),
                'car_model' => $this->resolvedCarModel(),
                'car_number' => $validated['carNumber'],
                'notes' => null,
            ]);

        $visit = Visit::query()->create([
            'client_id' => $client->id,
            'service_type' => $serviceType,
            'visit_date' => $visitStart,
            'visit_end_at' => $visitEnd,
            'price' => $validated['price'],
            'status' => $validated['status'],
            'notes' => $validated['visitNotes'],
            'came_from_reminder' => (bool) $validated['cameFromReminder'],
        ]);

        app(VisitReminderService::class)->syncAppointmentReminder($visit);

        $this->closeBookingModal();
    }

    public function closeBookingModal(): void
    {
        $this->reset([
            'showBookingModal',
            'selectedVisitId',
            'selectedDate',
            'selectedStartTime',
            'selectedEndTime',
            'existingClientId',
            'fullName',
            'phone',
            'carBrand',
            'carModel',
            'customCarBrand',
            'customCarModel',
            'carNumber',
            'serviceType',
            'customServiceType',
            'price',
            'cameFromReminder',
            'visitNotes',
        ]);

        $this->clientMode = 'new';
        $this->status = VisitStatus::Planned->value;
        $this->resetValidation();
    }

    /**
     * @return array<string, list<string>>
     */
    protected function rules(): array
    {
        $rules = [
            'selectedDate' => ['required', 'date'],
            'selectedStartTime' => ['required', 'date_format:H:i'],
            'selectedEndTime' => ['required', 'date_format:H:i'],
            'serviceType' => ['required', 'string', 'max:255'],
            'customServiceType' => ['nullable', 'string', 'max:255'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:planned,completed,cancelled'],
            'cameFromReminder' => ['boolean'],
            'visitNotes' => ['nullable', 'string', 'max:5000'],
        ];

        if ($this->clientMode === 'existing') {
            $rules['existingClientId'] = ['required', 'exists:clients,id'];

            return $rules;
        }

        return array_merge($rules, [
            'fullName' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:255'],
            'carBrand' => ['required', 'string', 'max:255'],
            'carModel' => ['required', 'string', 'max:255'],
            'customCarBrand' => ['nullable', 'string', 'max:255'],
            'customCarModel' => ['nullable', 'string', 'max:255'],
            'carNumber' => ['nullable', 'string', 'max:255'],
        ]);
    }

    /**
     * @return Collection<int, Client>
     */
    #[Computed]
    public function clients(): Collection
    {
        return Client::query()
            ->orderBy('full_name')
            ->get();
    }

    #[Computed]
    public function matchingClientByPhone(): ?Client
    {
        if ($this->clientMode !== 'new') {
            return null;
        }

        $normalizedPhone = $this->normalizePhone($this->phone);

        if ($normalizedPhone === '' || strlen($normalizedPhone) < 10) {
            return null;
        }

        return Client::query()
            ->get()
            ->first(function (Client $client) use ($normalizedPhone): bool {
                return $this->normalizePhone($client->phone) === $normalizedPhone;
            });
    }

    /**
     * @return list<string>
     */
    #[Computed]
    public function vehicleBrands(): array
    {
        return app(VehicleCatalogService::class)->brands();
    }

    /**
     * @return list<string>
     */
    #[Computed]
    public function availableModels(): array
    {
        if ($this->carBrand === '' || $this->carBrand === self::CUSTOM_BRAND) {
            return [];
        }

        return app(VehicleCatalogService::class)->modelsForBrand($this->carBrand);
    }

    #[Computed]
    public function customBrandValue(): string
    {
        return self::CUSTOM_BRAND;
    }

    #[Computed]
    public function customModelValue(): string
    {
        return self::CUSTOM_MODEL;
    }

    #[Computed]
    public function customBrandSelected(): bool
    {
        return $this->carBrand === self::CUSTOM_BRAND;
    }

    #[Computed]
    public function customModelSelected(): bool
    {
        return $this->carBrand === self::CUSTOM_BRAND || $this->carModel === self::CUSTOM_MODEL;
    }

    /**
     * @return array<string, string|null>
     */
    #[Computed]
    public function serviceCatalog(): array
    {
        return ServiceCatalogItem::query()
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (ServiceCatalogItem $item): array => [
                $item->name => $item->default_price !== null ? number_format((float) $item->default_price, 2, '.', '') : null,
            ])
            ->all();
    }

    /**
     * @return array<string, string|null>
     */
    #[Computed]
    public function editServiceCatalog(): array
    {
        $services = $this->serviceCatalog;

        if ($this->editServiceType !== '' && $this->editServiceType !== self::CUSTOM_SERVICE) {
            $services[$this->editServiceType] ??= $this->editPrice;
            ksort($services, SORT_NATURAL | SORT_FLAG_CASE);
        }

        return $services;
    }

    #[Computed]
    public function canManageServiceCatalog(): bool
    {
        return auth()->check();
    }

    #[Computed]
    public function customServiceValue(): string
    {
        return self::CUSTOM_SERVICE;
    }

    #[Computed]
    public function customServiceSelected(): bool
    {
        return $this->serviceType === self::CUSTOM_SERVICE;
    }

    #[Computed]
    public function editCustomServiceSelected(): bool
    {
        return $this->editServiceType === self::CUSTOM_SERVICE;
    }

    /**
     * @return list<array{date: string, heading: string, subheading: string, visits: list<array{id: int, top: int, height: int, client: string, service: string, time: string, status: string}>}>
     */
    #[Computed]
    public function scheduleDays(): array
    {
        $weekStart = CarbonImmutable::parse($this->weekStartsAt)->startOfDay();
        $visitsByDate = $this->scheduledVisits
            ->groupBy(fn (Visit $visit): string => $visit->visit_date->toDateString());

        $days = [];

        for ($offset = 0; $offset < 7; $offset++) {
            $day = $weekStart->addDays($offset);

            $days[] = [
                'date' => $day->toDateString(),
                'heading' => $day->translatedFormat('l'),
                'subheading' => $day->format('d.m'),
                'visits' => $visitsByDate
                    ->get($day->toDateString(), collect())
                    ->map(fn (Visit $visit): array => $this->mapVisitToScheduleBlock($visit))
                    ->values()
                    ->all(),
            ];
        }

        return $days;
    }

    /**
     * @return list<array{time: string, label: string}>
     */
    #[Computed]
    public function timeSlots(): array
    {
        $slots = [];
        $current = CarbonImmutable::createFromTime(self::SCHEDULE_START_HOUR, 0);
        $end = CarbonImmutable::createFromTime(self::SCHEDULE_END_HOUR, 0);

        while ($current->lessThan($end)) {
            $slots[] = [
                'time' => $current->format('H:i'),
                'label' => $current->format('H:i'),
            ];

            $current = $current->addMinutes(self::SLOT_MINUTES);
        }

        return $slots;
    }

    /**
     * @return Collection<int, Visit>
     */
    #[Computed]
    public function scheduledVisits(): Collection
    {
        $weekStart = CarbonImmutable::parse($this->weekStartsAt)->startOfDay();
        $weekEnd = $weekStart->addWeek();

        return Visit::query()
            ->with('client')
            ->where('visit_date', '<', $weekEnd)
            ->where(function ($query) use ($weekStart): void {
                $query
                    ->where('visit_end_at', '>', $weekStart)
                    ->orWhere(function ($nestedQuery) use ($weekStart): void {
                        $nestedQuery
                            ->whereNull('visit_end_at')
                            ->where('visit_date', '>=', $weekStart);
                    });
            })
            ->orderBy('visit_date')
            ->get();
    }

    public function render(): View
    {
        return view('livewire.visits-schedule-page');
    }

    #[Computed]
    public function selectedVisit(): ?Visit
    {
        if ($this->selectedVisitId === null) {
            return null;
        }

        return Visit::query()
            ->with('client')
            ->find($this->selectedVisitId);
    }

    /**
     * @return array{id: int, date: string, start: string, end: string, top: int, height: int, client: string, service: string, time: string, status: string, compact: bool}
     */
    private function mapVisitToScheduleBlock(Visit $visit): array
    {
        $scheduleStart = $visit->visit_date->copy()->setTime(self::SCHEDULE_START_HOUR, 0);
        $scheduleEnd = $visit->visit_date->copy()->setTime(self::SCHEDULE_END_HOUR, 0);
        $visitEnd = $visit->visit_end_at ?? $visit->visit_date->copy()->addHour();

        $visibleStart = $visit->visit_date->greaterThan($scheduleStart) ? $visit->visit_date : $scheduleStart;
        $visibleEnd = $visitEnd->lessThan($scheduleEnd) ? $visitEnd : $scheduleEnd;

        $top = max(
            0,
            (int) floor($scheduleStart->diffInMinutes($visibleStart) / self::SLOT_MINUTES) * self::SLOT_HEIGHT,
        );

        $height = max(
            self::SLOT_HEIGHT,
            (int) ceil($visibleStart->diffInMinutes($visibleEnd) / self::SLOT_MINUTES) * self::SLOT_HEIGHT,
        );

        $isCompact = $height <= self::SLOT_HEIGHT;

        return [
            'id' => $visit->id,
            'date' => $visit->visit_date->toDateString(),
            'start' => $visit->visit_date->format('H:i'),
            'end' => $visitEnd->format('H:i'),
            'top' => $top,
            'height' => $height,
            'client' => $visit->client->full_name,
            'service' => $visit->service_type,
            'status' => $visit->status->value,
            'compact' => $isCompact,
            'time' => sprintf(
                '%s - %s',
                $visit->visit_date->format('H:i'),
                $visitEnd->format('H:i'),
            ),
        ];
    }

    private function validateVehicleSelection(): void
    {
        if ($this->clientMode !== 'new') {
            return;
        }

        if ($this->carBrand !== self::CUSTOM_BRAND && ! in_array($this->carBrand, $this->vehicleBrands, true)) {
            $this->addError('carBrand', 'Оберіть марку зі списку або додайте нову.');
        }

        if ($this->carBrand === self::CUSTOM_BRAND && blank($this->customCarBrand)) {
            $this->addError('customCarBrand', 'Вкажіть нову марку авто.');
        }

        if (
            $this->carBrand !== self::CUSTOM_BRAND
            && $this->carModel !== self::CUSTOM_MODEL
            && ! in_array($this->carModel, $this->availableModels, true)
        ) {
            $this->addError('carModel', 'Оберіть модель зі списку або додайте нову.');
        }

        if ($this->customModelSelected && blank($this->customCarModel)) {
            $this->addError('customCarModel', 'Вкажіть нову модель авто.');
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            throw ValidationException::withMessages($this->getErrorBag()->toArray());
        }
    }

    private function validateServiceSelection(bool $editing = false): void
    {
        $serviceField = $editing ? 'editServiceType' : 'serviceType';
        $customServiceField = $editing ? 'editCustomServiceType' : 'customServiceType';
        $selectedService = $editing ? $this->editServiceType : $this->serviceType;
        $customService = $editing ? $this->editCustomServiceType : $this->customServiceType;
        $availableServices = $editing ? $this->editServiceCatalog : $this->serviceCatalog;

        if ($selectedService === self::CUSTOM_SERVICE) {
            if (! $this->canManageServiceCatalog) {
                $this->addError($serviceField, 'Лише адміністратор може додавати нові послуги.');
            }

            if (blank($customService)) {
                $this->addError($customServiceField, 'Вкажіть назву нової послуги.');
            }
        } elseif (! array_key_exists($selectedService, $availableServices)) {
            $this->addError($serviceField, 'Оберіть послугу зі списку або додайте нову.');
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            throw ValidationException::withMessages($this->getErrorBag()->toArray());
        }
    }

    private function resolvedServiceType(bool $editing = false): string
    {
        if (($editing ? $this->editServiceType : $this->serviceType) === self::CUSTOM_SERVICE) {
            return trim((string) ($editing ? $this->editCustomServiceType : $this->customServiceType));
        }

        return $editing ? $this->editServiceType : $this->serviceType;
    }

    private function storeCustomServiceIfNeeded(?string $price, bool $editing = false): void
    {
        $selectedService = $editing ? $this->editServiceType : $this->serviceType;

        if ($selectedService !== self::CUSTOM_SERVICE || ! $this->canManageServiceCatalog) {
            return;
        }

        $serviceCatalogItem = ServiceCatalogItem::query()->firstOrNew([
            'name' => $this->resolvedServiceType($editing),
        ]);

        if (! $serviceCatalogItem->exists || filled($price)) {
            $serviceCatalogItem->default_price = filled($price) ? $price : null;
        }

        $serviceCatalogItem->save();
    }

    private function defaultPriceForService(string $serviceName): ?string
    {
        if ($serviceName === '' || $serviceName === self::CUSTOM_SERVICE) {
            return null;
        }

        return $this->serviceCatalog[$serviceName] ?? null;
    }

    private function normalizePhone(?string $phone): string
    {
        return preg_replace('/\D+/', '', (string) $phone) ?? '';
    }

    private function resolvedCarBrand(): string
    {
        if ($this->carBrand === self::CUSTOM_BRAND) {
            return trim((string) $this->customCarBrand);
        }

        return $this->carBrand;
    }

    private function resolvedCarModel(): string
    {
        if ($this->customModelSelected) {
            return trim((string) $this->customCarModel);
        }

        return $this->carModel;
    }
}
