<?php

namespace App\Filament\Resources\Contacts\Pages;

use App\Filament\Exports\ContactExporter;
use App\Filament\Resources\Contacts\ContactResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;

class ListContacts extends ListRecords
{
    protected static string $resource = ContactResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),

            ExportAction::make()
                ->label('Esporta')
                ->icon('heroicon-o-arrow-down-tray')
                ->exporter(ContactExporter::class),
        ];
    }
}
