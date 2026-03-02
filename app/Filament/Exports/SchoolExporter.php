<?php

namespace App\Filament\Exports;

use App\Models\School;
use Filament\Actions\Exports\ExportColumn;

class SchoolExporter extends BaseExporter
{
    protected static ?string $model = School::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('ID'),
            ExportColumn::make('external_id')->label('Codice ETB'),
            ExportColumn::make('description')->label('Scuola'),
            ExportColumn::make('codice_fiscale')->label('Codice fiscale'),

            ExportColumn::make('status')->label('Stato'),
            ExportColumn::make('created_at')->label('Creato il'),
            ExportColumn::make('created_by.full_name')->label('Creato da'),
            ExportColumn::make('updated_at')->label('Aggiornato il'),
            ExportColumn::make('updated_by.full_name')->label('Aggiornato da'),

            ExportColumn::make('sort')->label('Ordinamento'),
        ];
    }
}
