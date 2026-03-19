<?php

namespace App\Livewire;

use App\Enums\ReminderStatus;
use App\Enums\VisitStatus;
use App\Models\Client;
use App\Models\Reminder;
use App\Models\Visit;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Dashboard')]
class DashboardPage extends Component
{
    public string $startDate = '';

    public string $endDate = '';

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->toDateString();
        $this->endDate = now()->toDateString();
    }

    public function updatedStartDate(string $value): void
    {
        if ($value > $this->endDate) {
            $this->endDate = $value;
        }
    }

    public function updatedEndDate(string $value): void
    {
        if ($value < $this->startDate) {
            $this->startDate = $value;
        }
    }

    public function usePeriodPreset(string $preset): void
    {
        $today = CarbonImmutable::today();

        match ($preset) {
            'week' => [$this->startDate, $this->endDate] = [$today->startOfWeek()->toDateString(), $today->toDateString()],
            'year' => [$this->startDate, $this->endDate] = [$today->startOfYear()->toDateString(), $today->toDateString()],
            default => [$this->startDate, $this->endDate] = [$today->startOfMonth()->toDateString(), $today->toDateString()],
        };
    }

    #[Computed]
    public function clientsTotal(): int
    {
        return Client::query()->count();
    }

    #[Computed]
    public function visitsTotal(): int
    {
        return $this->visitsQuery()->count();
    }

    #[Computed]
    public function remindersSent(): int
    {
        return $this->remindersQuery()
            ->where('status', ReminderStatus::Sent)
            ->count();
    }

    #[Computed]
    public function clientsReturned(): int
    {
        return $this->visitsQuery()
            ->where('came_from_reminder', true)
            ->distinct('client_id')
            ->count('client_id');
    }

    #[Computed]
    public function revenue(): float
    {
        return (float) $this->visitsQuery()
            ->where('status', VisitStatus::Completed)
            ->sum('price');
    }

    #[Computed]
    public function visitsToday(): int
    {
        return Visit::query()
            ->whereDate('visit_date', now()->toDateString())
            ->count();
    }

    /**
     * @return array{
     *     labels: array<int, string>,
     *     clients: array<int, int>,
     *     visits: array<int, int>,
     *     revenue: array<int, float>,
     *     has_data: bool
     * }
     */
    #[Computed]
    public function chart(): array
    {
        $start = $this->periodStart();
        $end = $this->periodEnd()->startOfDay();

        $groupedVisits = $this->visitsQuery()
            ->selectRaw('DATE(visit_date) as day')
            ->selectRaw('COUNT(DISTINCT client_id) as clients_count')
            ->selectRaw('COUNT(*) as visits_count')
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN status = ? THEN price ELSE 0 END), 0) as revenue_total',
                [VisitStatus::Completed->value],
            )
            ->groupByRaw('DATE(visit_date)')
            ->orderByRaw('DATE(visit_date)')
            ->get()
            ->keyBy('day');

        $days = collect();
        $cursor = $start;

        while ($cursor->lessThanOrEqualTo($end)) {
            $days->push($cursor->toDateString());
            $cursor = $cursor->addDay();
        }

        $labels = [];
        $clients = [];
        $visits = [];
        $revenue = [];

        foreach ($days as $day) {
            $labels[] = CarbonImmutable::parse($day)->format('d.m');
            $clients[] = (int) ($groupedVisits->get($day)?->clients_count ?? 0);
            $visits[] = (int) ($groupedVisits->get($day)?->visits_count ?? 0);
            $revenue[] = round((float) ($groupedVisits->get($day)?->revenue_total ?? 0), 2);
        }

        return [
            'labels' => $labels,
            'clients' => $clients,
            'visits' => $visits,
            'revenue' => $revenue,
            'has_data' => collect($clients)->contains(fn (int $value): bool => $value > 0)
                || collect($visits)->contains(fn (int $value): bool => $value > 0)
                || collect($revenue)->contains(fn (float $value): bool => $value > 0),
        ];
    }

    #[Computed]
    public function periodLabel(): string
    {
        return $this->periodStart()->format('d.m.Y').' - '.$this->periodEnd()->format('d.m.Y');
    }

    protected function periodStart(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->startDate)->startOfDay();
    }

    protected function periodEnd(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->endDate)->endOfDay();
    }

    protected function visitsQuery(): Builder
    {
        return Visit::query()
            ->whereBetween('visit_date', [$this->periodStart(), $this->periodEnd()]);
    }

    protected function remindersQuery(): Builder
    {
        return Reminder::query()
            ->whereBetween('sent_at', [$this->periodStart(), $this->periodEnd()]);
    }

    public function render(): View
    {
        return view('livewire.dashboard-page');
    }
}
