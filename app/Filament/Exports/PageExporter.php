<?php

namespace App\Filament\Exports;

use App\Models\Page;
use Filament\Actions\Exports\ExportColumn;

class PageExporter extends BaseExporter
{
    protected static ?string $model = Page::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('ID'),

            // Richiesta: per Template mostrare la description del Modello Generico.
            ExportColumn::make('template.templateType.description')->label('Modello Generico'),

            // Richiesta: per Pagina mostrare la description della sua page_type.
            ExportColumn::make('pageType.description')->label('Tipologia Pagina'),

            ExportColumn::make('order.external_id')->label('Ordine'),
            ExportColumn::make('school.description')->label('Scuola'),

            ExportColumn::make('position')->label('Posizione'),
            ExportColumn::make('description')->label('Nome'),

            ExportColumn::make('status')->label('Stato'),
            ExportColumn::make('created_at')->label('Creato il'),
            ExportColumn::make('created_by.full_name')->label('Creato da'),
            ExportColumn::make('updated_at')->label('Aggiornato il'),
            ExportColumn::make('updated_by.full_name')->label('Aggiornato da'),

            ExportColumn::make('sort')->label('Ordinamento'),
        ];
    }
}
