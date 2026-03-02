<?php

namespace App\Filament\Resources\TemplateTypes\Pages;

use App\Filament\Exports\TemplateTypeExporter;
use App\Filament\Resources\TemplateTypes\TemplateTypeResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;

class ListTemplateTypes extends ListRecords
{
    protected static string $resource = TemplateTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ExportAction::make('export')
                ->label('Esporta')
                ->icon('heroicon-o-arrow-down-tray')
                ->exporter(TemplateTypeExporter::class),
        ];
    }
}
