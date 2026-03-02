<?php

namespace App\Filament\Widgets\Admin;

use App\Filament\Widgets\Admin\Concerns\InteractsWithDashboardPeriod;
use App\Models\Order;
use App\Models\User;
use App\Models\School;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class OrdersKpiOverview extends StatsOverviewWidget
{
    use InteractsWithPageFilters;
    use InteractsWithDashboardPeriod;

    protected int|string|array $columnSpan = 'full';

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        [$from, $to] = $this->getDashboardDateRange();

        $days = max(1, $from->diffInDays($to) + 1);
        $prevFrom = $from->copy()->subDays($days);
        $prevTo = $to->copy()->subDays($days);

        $ordersCurrent = Order::query()
            ->where('status', '!=', 'deleted')
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $ordersPrevious = Order::query()
            ->where('status', '!=', 'deleted')
            ->whereBetween('created_at', [$prevFrom, $prevTo])
            ->count();

        $ordersDeltaText = $this->formatDeltaPercent($ordersCurrent, $ordersPrevious);

        $schoolsInvolved = Order::query()
            ->where('status', '!=', 'deleted')
            ->whereBetween('created_at', [$from, $to])
            ->distinct('school_id')
            ->count('school_id');

        $schoolsNew = School::query()
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $usersActive = User::query()
            ->where('status', '=', 'active')
            ->count();

        $now = Carbon::now();
        $deadlineTo = $now->copy()->addDays(7)->endOfDay();

        $upcomingDeadlines = Order::query()
            ->where('status', '!=', 'deleted')
            ->whereNotIn('status', ['completed'])
            ->where(function ($q) use ($now, $deadlineTo) {
                $q->whereBetween('deadline_collection', [$now, $deadlineTo])
                    ->orWhereBetween('deadline_annotation', [$now, $deadlineTo]);
            })
            ->count();

        return [
            Stat::make('Ordini (periodo)', $ordersCurrent)
                ->description($ordersDeltaText)
                ->descriptionIcon($ordersCurrent >= $ordersPrevious ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down')
                ->color('primary')
                ->icon('heroicon-o-clipboard-document-check'),

            Stat::make('Scuole coinvolte', $schoolsInvolved)
                ->description($schoolsNew > 0 ? "+{$schoolsNew} nuove" : 'Nessuna nuova')
                ->color('info')
                ->icon('heroicon-o-academic-cap'),

            Stat::make('Utenti attivi', $usersActive)
                ->description('Stato: attivi')
                ->color('success')
                ->icon('heroicon-o-users'),

            Stat::make('Scadenze prossime (7g)', $upcomingDeadlines)
                ->description($upcomingDeadlines > 0 ? 'Richiede attenzione' : 'Nessuna urgenza')
                ->color($upcomingDeadlines > 0 ? 'warning' : 'gray')
                ->icon('heroicon-o-clock'),
        ];
    }

    private function formatDeltaPercent(int $current, int $previous): string
    {
        if ($previous <= 0) {
            return '—';
        }

        $delta = (($current - $previous) / $previous) * 100;
        $rounded = (int) round($delta);

        $sign = $rounded > 0 ? '+' : '';

        return "{$sign}{$rounded}% vs prev";
    }
}
