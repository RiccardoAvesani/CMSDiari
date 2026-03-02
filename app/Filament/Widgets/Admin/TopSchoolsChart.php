<?php

namespace App\Filament\Widgets\Admin;

use App\Filament\Widgets\Admin\Concerns\InteractsWithDashboardPeriod;
use App\Models\Order;
use App\Models\School;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class TopSchoolsChart extends ChartWidget
{
    use InteractsWithPageFilters;
    use InteractsWithDashboardPeriod;

    protected ?string $heading = 'Top scuole';

    protected int|string|array $columnSpan = 'full';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getMaxHeight(): ?string
    {
        return '260px';
    }

    protected function getData(): array
    {
        [$from, $to] = $this->getDashboardDateRange();

        $rows = Order::query()
            ->selectRaw('school_id, COUNT(*) as orders_count')
            ->where('status', '!=', 'deleted')
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('school_id')
            ->orderByDesc('orders_count')
            ->limit(10)
            ->get();

        $schoolIds = $rows->pluck('school_id')->filter()->all();

        $schools = School::query()
            ->whereIn('id', $schoolIds)
            ->get(['id', 'description'])
            ->keyBy('id');

        $labels = [];
        $data = [];

        foreach ($rows as $row) {
            $school = $schools->get($row->school_id);

            $labels[] = $school?->description ?? ('Scuola ' . $row->school_id);
            $data[] = (int) $row->orders_count;
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Ordini',
                    'data' => $data,
                    'backgroundColor' => '#06b6d4',
                    'borderRadius' => 6,

                    'barPercentage' => 0.55,
                    'categoryPercentage' => 0.75,
                    'maxBarThickness' => 32,
                ],
            ],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'maintainAspectRatio' => false,
            'layout' => [
                'padding' => [
                    'left' => 12,
                    'right' => 12,
                    'top' => 0,
                    'bottom' => 0,
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                    'ticks' => [
                        'maxRotation' => 0,
                        'minRotation' => 0,
                    ],
                ],
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
        ];
    }
}
