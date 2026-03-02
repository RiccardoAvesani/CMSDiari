<?php

namespace App\Filament\Exports;

use App\Models\Location;
use Filament\Actions\Exports\ExportColumn;

class LocationExporter extends BaseExporter
{
    protected static ?string $model = Location::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('ID'),

            ExportColumn::make('school.description')->label('Scuola'),
            ExportColumn::make('description')->label('Descrizione'),
            ExportColumn::make('address')->label('Indirizzo'),

            ExportColumn::make('status')->label('Stato'),
            ExportColumn::make('created_at')->label('Creato il'),
            ExportColumn::make('created_by.full_name')->label('Creato da'),
            ExportColumn::make('updated_at')->label('Aggiornato il'),
            ExportColumn::make('updated_by.full_name')->label('Aggiornato da'),

            ExportColumn::make('sort')->label('Ordinamento'),
        ];
    }
}
