<?php

namespace App\Filament\Resources\Locations\Pages;

use App\Filament\Exports\LocationExporter;
use App\Filament\Resources\Locations\LocationResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;

class ListLocations extends ListRecords
{
    protected static string $resource = LocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ExportAction::make()
                ->label('Esporta')
                ->icon('heroicon-o-arrow-down-tray')
                ->exporter(LocationExporter::class),
        ];
    }
}
