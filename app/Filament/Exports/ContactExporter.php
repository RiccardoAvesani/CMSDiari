<?php

namespace App\Filament\Exports;

use App\Models\Contact;
use Filament\Actions\Exports\ExportColumn;

class ContactExporter extends BaseExporter
{
    protected static ?string $model = Contact::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('ID'),

            ExportColumn::make('location.school.description')->label('Scuola'),
            ExportColumn::make('location.description')->label('Sede'),

            ExportColumn::make('firstname')->label('Nome'),
            ExportColumn::make('lastname')->label('Cognome'),
            ExportColumn::make('telephone')->label('Telefono'),
            ExportColumn::make('email')->label('Email'),

            ExportColumn::make('status')->label('Stato'),
            ExportColumn::make('created_at')->label('Creato il'),
            ExportColumn::make('created_by.full_name')->label('Creato da'),
            ExportColumn::make('updated_at')->label('Aggiornato il'),
            ExportColumn::make('updated_by.full_name')->label('Aggiornato da'),

            ExportColumn::make('sort')->label('Ordinamento'),
        ];
    }
}
