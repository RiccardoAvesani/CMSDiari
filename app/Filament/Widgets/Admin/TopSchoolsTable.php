<?php

namespace App\Filament\Widgets\Admin;

use App\Filament\Widgets\Admin\Concerns\InteractsWithDashboardPeriod;
use App\Models\Order;
use App\Models\School;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Facades\DB;

class TopSchoolsTable extends TableWidget
{
    use InteractsWithPageFilters;
    use InteractsWithDashboardPeriod;

    protected static ?string $heading = 'Top scuole';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getSchoolsQuery())
            ->defaultSort('sort')
            ->columns([
                Tables\Columns\TextColumn::make('description')
                    ->label('Scuola')
                    ->searchable()
                    ->wrap()
                    ->url(fn(School $record): string => route('filament.admin.resources.schools.view', ['record' => $record])),

                Tables\Columns\TextColumn::make('orders_count')
                    ->label('Ordini')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\ViewColumn::make('mock_completion')
                    ->label('Completamento medio')
                    ->view('filament.widgets.admin.columns.completion-bar')
                    ->state(function (School $record): int {
                        $avg = Order::query()
                            ->where('status', '!=', 'deleted')
                            ->where('school_id', '=', $record->id)
                            ->avg(DB::raw("
                                CASE status
                                    WHEN 'new' THEN 5
                                    WHEN 'collection' THEN 45
                                    WHEN 'draft' THEN 75
                                    WHEN 'annotation' THEN 85
                                    WHEN 'approved' THEN 95
                                    WHEN 'production' THEN 100
                                    WHEN 'completed' THEN 100
                                    ELSE 0
                                END
                            "));

                        return (int) round($avg ?? 0);
                    }),

                Tables\Columns\TextColumn::make('prevailing_status')
                    ->label('Stato prevalente')
                    ->state(function (School $record): string {
                        $row = Order::query()
                            ->selectRaw('status, COUNT(*) as c')
                            ->where('status', '!=', 'deleted')
                            ->where('school_id', '=', $record->id)
                            ->groupBy('status')
                            ->orderByDesc('c')
                            ->first();

                        return (string) ($row?->status ?? 'new');
                    })
                    ->formatStateUsing(fn(string $state): string => $this->labelForStatus($state))
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'new' => 'primary',
                        'collection' => 'info',
                        'draft' => 'warning',
                        'annotation' => 'warning',
                        'approved' => 'success',
                        'production' => 'info',
                        'completed' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('view_link')
                    ->label('')
                    ->state(fn(): string => 'Vedi')
                    ->url(fn(School $record): string => route('filament.admin.resources.schools.view', ['record' => $record]))
                    ->icon('heroicon-o-eye')
                    ->alignCenter(),
            ])
            ->paginated(false);
    }

    private function getSchoolsQuery()
    {
        [$from, $to] = $this->getDashboardDateRange();

        return School::query()
            ->select('schools.*')
            ->selectRaw('COUNT(orders.id) as orders_count')
            ->leftJoin('orders', function ($join) use ($from, $to) {
                $join->on('orders.school_id', '=', 'schools.id')
                    ->where('orders.status', '!=', 'deleted')
                    ->whereBetween('orders.created_at', [$from, $to]);
            })
            ->groupBy('schools.id');
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
