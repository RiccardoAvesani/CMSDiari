<?php

namespace App\Filament\Exports;

use App\Models\Invitation;
use Filament\Actions\Exports\ExportColumn;

class InvitationExporter extends BaseExporter
{
    protected static ?string $model = Invitation::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('ID'),

            ExportColumn::make('email')->label('Email'),
            ExportColumn::make('role')->label('Ruolo'),

            ExportColumn::make('school.description')->label('Scuola'),
            ExportColumn::make('order.external_id')->label('Ordine'),
            ExportColumn::make('user.full_name')->label('Utente'),

            ExportColumn::make('sent_at')->label('Inviato il'),
            ExportColumn::make('received_at')->label('Ricevuto il'),
            ExportColumn::make('received_via')->label('Ricevuto via'),
            ExportColumn::make('expires_at')->label('Scade il'),
            ExportColumn::make('registered_at')->label('Registrato il'),

            ExportColumn::make('status')->label('Stato'),
            ExportColumn::make('created_at')->label('Creato il'),
            ExportColumn::make('created_by.full_name')->label('Creato da'),
            ExportColumn::make('updated_at')->label('Aggiornato il'),
            ExportColumn::make('updated_by.full_name')->label('Aggiornato da'),

            ExportColumn::make('sort')->label('Ordinamento'),
        ];
    }
}
