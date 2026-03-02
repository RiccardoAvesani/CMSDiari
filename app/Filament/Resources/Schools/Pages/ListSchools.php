<?php

namespace App\Filament\Resources\Schools\Pages;

use App\Filament\Exports\SchoolExporter;
use App\Filament\Resources\Schools\SchoolResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;

class ListSchools extends ListRecords
{
    protected static string $resource = SchoolResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ExportAction::make()
                ->label('Esporta')
                ->icon('heroicon-o-arrow-down-tray')
                ->exporter(SchoolExporter::class),
        ];
    }
}
