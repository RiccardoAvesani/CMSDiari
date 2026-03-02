<?php

namespace App\Filament\Widgets\Admin\Concerns;

use Illuminate\Support\Carbon;

trait InteractsWithDashboardPeriod
{
    /**
     * Ritorna una coppia [from, to] (Carbon) in base ai filtri della Dashboard.
     */
    protected function getDashboardDateRange(): array
    {
        $period = data_get($this->filters ?? [], 'period', '30d');

        $now = Carbon::now();
        $to = $now->copy()->endOfDay();

        if ($period === '7d') {
            $from = $now->copy()->subDays(6)->startOfDay();
            return [$from, $to];
        }

        if ($period === 'month') {
            $from = $now->copy()->startOfMonth();
            return [$from, $to];
        }

        if ($period === 'custom') {
            $fromFilter = data_get($this->filters ?? [], 'from');
            $toFilter = data_get($this->filters ?? [], 'to');

            $from = $fromFilter ? Carbon::parse($fromFilter)->startOfDay() : $now->copy()->subDays(29)->startOfDay();
            $toCustom = $toFilter ? Carbon::parse($toFilter)->endOfDay() : $to;

            if ($from->greaterThan($toCustom)) {
                [$from, $toCustom] = [$toCustom->copy()->startOfDay(), $from->copy()->endOfDay()];
            }

            return [$from, $toCustom];
        }

        // Default: 30d
        $from = $now->copy()->subDays(29)->startOfDay();

        return [$from, $to];
    }
}
