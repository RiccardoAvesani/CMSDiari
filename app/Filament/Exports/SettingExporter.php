<?php

namespace App\Filament\Exports;

use App\Models\Setting;
use Filament\Actions\Exports\ExportColumn;

class SettingExporter extends BaseExporter
{
    protected static ?string $model = Setting::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('ID'),

            ExportColumn::make('description')->label('Nome'),
            ExportColumn::make('instructions')->label('Descrizione'),
            ExportColumn::make('value')->label('Valore'),
            ExportColumn::make('environment')->label('Ambiente'),
            ExportColumn::make('permission')->label('Permessi'),
            ExportColumn::make('is_active')->label('Abilitata'),
            ExportColumn::make('user.full_name')->label('Utente'),


            ExportColumn::make('status')->label('Stato'),
            ExportColumn::make('created_at')->label('Creato il'),
            ExportColumn::make('created_by.full_name')->label('Creato da'),
            ExportColumn::make('updated_at')->label('Aggiornato il'),
            ExportColumn::make('updated_by.full_name')->label('Aggiornato da'),

            ExportColumn::make('sort')->label('Ordinamento'),
        ];
    }
}
