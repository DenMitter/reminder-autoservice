<?php

namespace App\Livewire;

use App\Models\Client;
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

    public string $carBrand = '';

    public string $carModel = '';

    public ?string $carNumber = null;

    public ?string $notes = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showClientModal = true;
    }

    public function openEditModal(int $clientId): void
    {
        $client = Client::query()->findOrFail($clientId);

        $this->editingClientId = $client->id;
        $this->fullName = $client->full_name;
        $this->phone = $client->phone;
        $this->carBrand = $client->car_brand;
        $this->carModel = $client->car_model;
        $this->carNumber = $client->car_number;
        $this->notes = $client->notes;
        $this->showClientModal = true;
    }

    public function saveClient(): void
    {
        $validated = $this->validate([
            'fullName' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'carBrand' => ['required', 'string', 'max:100'],
            'carModel' => ['required', 'string', 'max:100'],
            'carNumber' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        Client::query()->updateOrCreate(
            ['id' => $this->editingClientId],
            [
                'full_name' => $validated['fullName'],
                'phone' => $validated['phone'],
                'car_brand' => $validated['carBrand'],
                'car_model' => $validated['carModel'],
                'car_number' => $validated['carNumber'],
                'notes' => $validated['notes'],
            ],
        );

        $this->showClientModal = false;
        $this->resetForm();
    }

    #[Computed]
    public function clients(): LengthAwarePaginator
    {
        return Client::query()
            ->withCount('visits')
            ->when(
                $this->search !== '',
                fn (Builder $query): Builder => $query->where(function (Builder $subQuery): Builder {
                    return $subQuery
                        ->where('full_name', 'like', "%{$this->search}%")
                        ->orWhere('phone', 'like', "%{$this->search}%")
                        ->orWhere('car_brand', 'like', "%{$this->search}%")
                        ->orWhere('car_model', 'like', "%{$this->search}%")
                        ->orWhere('car_number', 'like', "%{$this->search}%");
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
            'carBrand',
            'carModel',
            'carNumber',
            'notes',
        ]);

        $this->resetValidation();
    }
}
