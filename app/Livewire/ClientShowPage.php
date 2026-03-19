<?php

namespace App\Livewire;

use App\Models\Client;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Картка клієнта')]
class ClientShowPage extends Component
{
    public Client $client;

    public function mount(Client $client): void
    {
        $this->client = $client;
    }

    public function render(): View
    {
        $client = $this->client->load([
            'visits' => fn ($query) => $query->latest('visit_date'),
            'reminders' => fn ($query) => $query->latest('send_at'),
        ]);

        return view('livewire.client-show-page', [
            'client' => $client,
        ]);
    }
}
