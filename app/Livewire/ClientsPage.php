<?php

namespace App\Livewire;

use App\Models\Client;
use App\Models\ClientVehicle;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Клієнти')]
class ClientsPage extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showClientModal = false;

    public ?int $editingClientId = null;

    public string $fullName = '';

    public string $phone = '';

    /**
     * @var array<int, array{id: int|null, carBrand: string, carModel: string, carNumber: string|null}>
     */
    public array $vehicles = [];

    public ?string $notes = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->vehicles = [$this->emptyVehicleFormRow()];
        $this->showClientModal = true;
    }

    public function openEditModal(int $clientId): void
    {
        $client = Client::query()
            ->with('vehicles')
            ->findOrFail($clientId);

        $this->editingClientId = $client->id;
        $this->fullName = $client->full_name;
        $this->phone = $client->phone;
        $this->vehicles = $client->vehicles
            ->map(fn (ClientVehicle $vehicle): array => [
                'id' => $vehicle->id,
                'carBrand' => $vehicle->car_brand,
                'carModel' => $vehicle->car_model,
                'carNumber' => $vehicle->car_number,
            ])
            ->values()
            ->all();

        if ($this->vehicles === []) {
            $this->vehicles = [$this->emptyVehicleFormRow()];
        }

        $this->notes = $client->notes;
        $this->showClientModal = true;
    }

    public function addVehicle(): void
    {
        $this->vehicles[] = $this->emptyVehicleFormRow();
    }

    public function removeVehicle(int $index): void
    {
        if (! array_key_exists($index, $this->vehicles) || count($this->vehicles) === 1) {
            return;
        }

        unset($this->vehicles[$index]);
        $this->vehicles = array_values($this->vehicles);
    }

    public function saveClient(): void
    {
        $validated = $this->validate([
            'fullName' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'vehicles' => ['required', 'array', 'min:1'],
            'vehicles.*.carBrand' => ['required', 'string', 'max:100'],
            'vehicles.*.carModel' => ['required', 'string', 'max:100'],
            'vehicles.*.carNumber' => ['nullable', 'string', 'max:30'],
        ]);

        /** @var array{carBrand: string, carModel: string, carNumber: string|null} $primaryVehicle */
        $primaryVehicle = $validated['vehicles'][0];

        $client = Client::query()->updateOrCreate(
            ['id' => $this->editingClientId],
            [
                'full_name' => $validated['fullName'],
                'phone' => $validated['phone'],
                'car_brand' => $primaryVehicle['carBrand'],
                'car_model' => $primaryVehicle['carModel'],
                'car_number' => $primaryVehicle['carNumber'],
                'notes' => $validated['notes'],
            ],
        );

        $this->syncVehicles($client);
        $client->syncPrimaryVehicleAttributes();

        $this->showClientModal = false;
        $this->resetForm();
    }

    #[Computed]
    public function clients(): LengthAwarePaginator
    {
        return Client::query()
            ->with('primaryVehicle')
            ->withCount(['visits', 'vehicles'])
            ->when(
                $this->search !== '',
                fn (Builder $query): Builder => $query->where(function (Builder $subQuery): Builder {
                    return $subQuery
                        ->where('full_name', 'like', "%{$this->search}%")
                        ->orWhere('phone', 'like', "%{$this->search}%")
                        ->orWhereHas('vehicles', function (Builder $vehicleQuery): void {
                            $vehicleQuery
                                ->where('car_brand', 'like', "%{$this->search}%")
                                ->orWhere('car_model', 'like', "%{$this->search}%")
                                ->orWhere('car_number', 'like', "%{$this->search}%");
                        });
                }),
            )
            ->latest()
            ->paginate(10);
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.clients-page');
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingClientId',
            'fullName',
            'phone',
            'vehicles',
            'notes',
        ]);

        $this->resetValidation();
    }

    /**
     * @return array{id: null, carBrand: string, carModel: string, carNumber: null}
     */
    private function emptyVehicleFormRow(): array
    {
        return [
            'id' => null,
            'carBrand' => '',
            'carModel' => '',
            'carNumber' => null,
        ];
    }

    private function syncVehicles(Client $client): void
    {
        $persistedVehicleIds = [];

        foreach ($this->vehicles as $vehicleData) {
            $vehicleId = $vehicleData['id'] ?? null;
            $attributes = [
                'car_brand' => $vehicleData['carBrand'],
                'car_model' => $vehicleData['carModel'],
                'car_number' => $vehicleData['carNumber'] ?: null,
            ];

            $vehicle = $vehicleId === null
                ? $client->vehicles()->create($attributes)
                : tap(
                    $client->vehicles()->findOrFail($vehicleId),
                    fn (ClientVehicle $existingVehicle) => $existingVehicle->update($attributes),
                );

            $persistedVehicleIds[] = $vehicle->id;
        }

        $client->vehicles()
            ->whereNotIn('id', $persistedVehicleIds)
            ->delete();
    }
}
