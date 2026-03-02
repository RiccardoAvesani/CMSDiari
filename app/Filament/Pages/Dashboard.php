<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use App\Filament\Widgets\Admin\DataCollectionTrendChart;
use App\Filament\Widgets\Admin\OrdersByStatusChart;
use App\Filament\Widgets\Admin\OrdersKpiOverview;
use App\Filament\Widgets\Admin\TopSchoolsChart;
use App\Filament\Widgets\Admin\TopSchoolsTable;

class Dashboard extends BaseDashboard
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-home';

    /**
     * 3 colonne: ci servono per avere 3 grafici affiancati.
     */
    protected int|string|array $columns = [
        'default' => 1,
        'xl' => 3,
    ];

    public function getHeaderWidgets(): array
    {
        return [
            OrdersKpiOverview::class,
        ];
    }

    public function getWidgets(): array
    {
        return [
            OrdersByStatusChart::class,
            DataCollectionTrendChart::class,
            TopSchoolsChart::class,
            TopSchoolsTable::class,
        ];
    }

    /**
     * Filtro “Periodo”.
     */
    public function getFiltersFormSchema(): array
    {
        return [
            Select::make('period')
                ->label('Periodo')
                ->options([
                    '7d' => 'Ultimi 7 giorni',
                    '30d' => 'Ultimi 30 giorni',
                    'month' => 'Mese corrente',
                    'custom' => 'Custom',
                ])
                ->default('30d')
                ->live(),

            DatePicker::make('from')
                ->label('Dal')
                ->native(false)
                ->visible(fn($get): bool => $get('period') === 'custom')
                ->live(),

            DatePicker::make('to')
                ->label('Al')
                ->native(false)
                ->visible(fn($get): bool => $get('period') === 'custom')
                ->live(),
        ];
    }
}
