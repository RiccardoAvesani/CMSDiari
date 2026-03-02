<?php

namespace App\Filament\Widgets\Admin;

use App\Filament\Widgets\Admin\Concerns\InteractsWithDashboardPeriod;
use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class OrdersByStatusChart extends ChartWidget
{
    use InteractsWithPageFilters;
    use InteractsWithDashboardPeriod;

    protected ?string $heading = 'Ordini per stato';

    protected int|string|array $columnSpan = [
        'default' => 1,
        'xl' => 1,
    ];

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        [$from, $to] = $this->getDashboardDateRange();

        $statusOrder = [
            'new',
            'collection',
            'draft',
            'annotation',
            'approved',
            'production',
            'completed',
        ];

        $counts = Order::query()
            ->selectRaw('status, COUNT(*) as c')
            ->where('status', '!=', 'deleted')
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('status')
            ->pluck('c', 'status')
            ->all();

        $labels = array_map(fn(string $s): string => $this->labelForStatus($s), $statusOrder);

        $data = array_map(fn(string $s): int => (int) ($counts[$s] ?? 0), $statusOrder);

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Ordini',
                    'data' => $data,
                    'backgroundColor' => [
                        '#2563eb', // new
                        '#06b6d4', // collection
                        '#f59e0b', // draft
                        '#f97316', // annotation
                        '#16a34a', // approved
                        '#111827', // production
                        '#10b981', // completed
                    ],
                    'borderWidth' => 2,
                    'borderColor' => '#ffffff',
                ],
            ],
        ];
    }

    private function labelForStatus(string $status): string
    {
        return match ($status) {
            'new' => 'Nuovo',
            'collection' => 'In raccolta',
            'draft' => 'Bozza',
            'annotation' => 'In correzione',
            'approved' => 'Approvato',
            'production' => 'In produzione',
            'completed' => 'Completato',
            default => $status,
        };
    }
}
