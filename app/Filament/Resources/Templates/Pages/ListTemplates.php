<?php

namespace App\Filament\Resources\Templates\Pages;

use App\Filament\Exports\TemplateExporter;
use App\Filament\Resources\Templates\TemplateResource;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;

class ListTemplates extends ListRecords
{
    protected static string $resource = TemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make('export')
                ->label('Esporta')
                ->icon('heroicon-o-arrow-down-tray')
                ->exporter(TemplateExporter::class),
        ];
    }
}
