<?php

namespace App\Livewire;

use App\Models\ServiceCatalogItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Послуги')]
class ServicesPage extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showServiceModal = false;

    public ?int $editingServiceId = null;

    public string $name = '';

    public ?string $defaultPrice = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showServiceModal = true;
    }

    public function openEditModal(int $serviceId): void
    {
        $service = ServiceCatalogItem::query()->findOrFail($serviceId);

        $this->editingServiceId = $service->id;
        $this->name = $service->name;
        $this->defaultPrice = $service->default_price !== null ? (string) $service->default_price : null;
        $this->showServiceModal = true;
        $this->resetValidation();
    }

    public function closeServiceModal(): void
    {
        $this->showServiceModal = false;
        $this->resetForm();
    }

    public function saveService(): void
    {
        $validated = $this->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('service_catalog_items', 'name')->ignore($this->editingServiceId),
            ],
            'defaultPrice' => ['nullable', 'numeric', 'min:0'],
        ]);

        ServiceCatalogItem::query()->updateOrCreate(
            ['id' => $this->editingServiceId],
            [
                'name' => trim($validated['name']),
                'default_price' => $validated['defaultPrice'],
            ],
        );

        $this->closeServiceModal();
    }

    public function deleteService(int $serviceId): void
    {
        ServiceCatalogItem::query()->findOrFail($serviceId)->delete();
    }

    #[Computed]
    public function services(): LengthAwarePaginator
    {
        return ServiceCatalogItem::query()
            ->when(
                $this->search !== '',
                fn ($query) => $query->where('name', 'like', "%{$this->search}%"),
            )
            ->orderBy('name')
            ->paginate(12);
    }

    public function render(): View
    {
        return view('livewire.services-page');
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingServiceId',
            'name',
            'defaultPrice',
        ]);

        $this->resetValidation();
    }
}
