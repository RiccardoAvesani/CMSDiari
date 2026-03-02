<?php

namespace App\Filament\Exports;

use App\Models\Order;
use Filament\Actions\Exports\ExportColumn;

class OrderExporter extends BaseExporter
{
    protected static ?string $model = Order::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('ID'),

            ExportColumn::make('external_id')->label('Codice ETB'),

            ExportColumn::make('campaign.description')->label('Campagna'),
            ExportColumn::make('school.description')->label('Scuola'),

            // Richiesta: per Template (modello compilato) mostrare la description del Modello Generico.
            ExportColumn::make('template.templateType.description')->label('Modello Generico'),

            ExportColumn::make('quantity')->label('Quantità'),
            ExportColumn::make('deadline_collection')->label('Scad. raccolta'),
            ExportColumn::make('deadline_annotation')->label('Scad. correzioni'),

            ExportColumn::make('status')->label('Stato'),
            ExportColumn::make('created_at')->label('Creato il'),
            ExportColumn::make('created_by.full_name')->label('Creato da'),
            ExportColumn::make('updated_at')->label('Aggiornato il'),
            ExportColumn::make('updated_by.full_name')->label('Aggiornato da'),

            ExportColumn::make('sort')->label('Ordinamento'),
        ];
    }
}
