<?php

namespace App\Filament\Exports;

use App\Models\TemplateType;
use Filament\Actions\Exports\ExportColumn;

class TemplateTypeExporter extends BaseExporter
{
    protected static ?string $model = TemplateType::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('ID'),

            ExportColumn::make('description')->label('Nome'),
            ExportColumn::make('max_pages')->label('Numero massimo pagine personalizzabili'),

            ExportColumn::make('status')->label('Stato'),
            ExportColumn::make('created_at')->label('Creato il'),
            ExportColumn::make('created_by.full_name')->label('Creato da'),
            ExportColumn::make('updated_at')->label('Aggiornato il'),
            ExportColumn::make('updated_by.full_name')->label('Aggiornato da'),

            ExportColumn::make('sort')->label('Ordinamento'),
        ];
    }
}
