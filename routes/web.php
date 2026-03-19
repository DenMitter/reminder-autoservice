<?php

use App\Livewire\ClientShowPage;
use App\Livewire\ClientsPage;
use App\Livewire\DashboardPage;
use App\Livewire\RemindersPage;
use App\Livewire\ServicesPage;
use App\Livewire\VisitsSchedulePage;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
})->name('home');

Route::middleware(['auth'])->group(function () {
    Route::livewire('dashboard', DashboardPage::class)->name('dashboard');
    Route::livewire('clients', ClientsPage::class)->name('clients.index');
    Route::livewire('clients/{client}', ClientShowPage::class)->name('clients.show');
    Route::redirect('visits', '/visits/schedule')->name('visits.index');
    Route::livewire('visits/schedule', VisitsSchedulePage::class)->name('visits.schedule');
    Route::livewire('services', ServicesPage::class)->name('services.index');
    Route::livewire('reminders', RemindersPage::class)->name('reminders.index');
});

require __DIR__.'/settings.php';
