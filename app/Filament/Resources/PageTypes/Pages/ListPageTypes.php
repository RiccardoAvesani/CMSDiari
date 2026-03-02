<?php

namespace App\Filament\Resources\PageTypes\Pages;

use App\Filament\Exports\PageTypeExporter;
use App\Filament\Resources\PageTypes\PageTypeResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;

class ListPageTypes extends ListRecords
{
    protected static string $resource = PageTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),

            ExportAction::make()
                ->label('Esporta')
                ->icon('heroicon-o-arrow-down-tray')
                ->exporter(PageTypeExporter::class),
        ];
    }
}
