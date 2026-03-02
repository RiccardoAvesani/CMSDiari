<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Filament\Exports\PageExporter;
use App\Filament\Resources\Pages\PageResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;

class ListPages extends ListRecords
{
    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),

            ExportAction::make()
                ->label('Esporta')
                ->icon('heroicon-o-arrow-down-tray')
                ->exporter(PageExporter::class),
        ];
    }
}
