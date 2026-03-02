<?php

namespace App\Filament\Exports;

use App\Models\PageType;
use Filament\Actions\Exports\ExportColumn;

class PageTypeExporter extends BaseExporter
{
    protected static ?string $model = PageType::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('ID'),
            ExportColumn::make('description')->label('Nome'),
            ExportColumn::make('space')->label('Spazio'),
            ExportColumn::make('max_pages')->label('Max occorrenze'),
            ExportColumn::make('icon_url')->label('Icona'),

            ExportColumn::make('status')->label('Stato'),
            ExportColumn::make('created_at')->label('Creato il'),
            ExportColumn::make('created_by.full_name')->label('Creato da'),
            ExportColumn::make('updated_at')->label('Aggiornato il'),
            ExportColumn::make('updated_by.full_name')->label('Aggiornato da'),

            ExportColumn::make('sort')->label('Ordinamento'),
        ];
    }
}
