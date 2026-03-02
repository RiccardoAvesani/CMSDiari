<?php

namespace App\Filament\Resources\Campaigns\Pages;

use App\Filament\Exports\CampaignExporter;
use App\Filament\Resources\Campaigns\CampaignResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;

class ListCampaigns extends ListRecords
{
    protected static string $resource = CampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ExportAction::make()
                ->label('Esporta')
                ->icon('heroicon-o-arrow-down-tray')
                ->exporter(CampaignExporter::class),
        ];
    }
}
