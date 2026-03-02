<?php

namespace App\Filament\Widgets\Admin;

use App\Filament\Widgets\Admin\Concerns\InteractsWithDashboardPeriod;
use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Carbon;

class DataCollectionTrendChart extends ChartWidget
{
    use InteractsWithPageFilters;
    use InteractsWithDashboardPeriod;

    protected ?string $heading = 'Trend raccolta dati';

    protected int|string|array $columnSpan = [
        'default' => 1,
        'xl' => 1,
    ];

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        [$from, $to] = $this->getDashboardDateRange();

        $orders = Order::query()
            ->where('status', '!=', 'deleted')
            ->whereBetween('created_at', [$from, $to])
            ->get(['id', 'status']);

        $base = $orders->count() > 0
            ? (int) round($orders->avg(fn($o) => $this->mockCompletionPercentFromStatus((string) $o->status)) ?? 0)
            : 0;

        // MOCK: finché non esiste la compilazione reale dei Template, costruiamo un trend credibile
        // attorno a una base derivata dallo stato degli ordini nel periodo.
        $points = 7;
        $values = [];

        for ($i = 0; $i < $points; $i++) {
            $offset = (int) round((-12 + ($i * 18)) / max(1, $points - 1));
            $values[] = max(0, min(100, $base + $offset));
        }

        $labels = $this->buildLabels($from, $to, $points);

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Completamento medio %',
                    'data' => $values,
                    'borderColor' => '#16a34a',
                    'backgroundColor' => 'rgba(22, 163, 74, 0.12)',
                    'tension' => 0.35,
                    'fill' => true,
                    'pointRadius' => 3,
                    'pointBackgroundColor' => '#16a34a',
                ],
            ],
        ];
    }

    private function buildLabels(Carbon $from, Carbon $to, int $points): array
    {
        $totalDays = max(1, $from->diffInDays($to));
        $step = max(1, (int) floor($totalDays / max(1, $points - 1)));

        $labels = [];
        $cursor = $from->copy();

        for ($i = 0; $i < $points; $i++) {
            $labels[] = $cursor->format('d M');
            $cursor = $cursor->addDays($step);

            if ($cursor->greaterThan($to)) {
                $cursor = $to->copy();
            }
        }

        return $labels;
    }

    private function mockCompletionPercentFromStatus(string $status): int
    {
        // MOCK mapping provvisorio. In futuro reale dal Template istanza e struttura compilata.
        return match ($status) {
            'new' => 5,
            'collection' => 45,
            'draft' => 75,
            'annotation' => 85,
            'approved' => 95,
            'production' => 100,
            'completed' => 100,
            default => 0,
        };
    }
}
