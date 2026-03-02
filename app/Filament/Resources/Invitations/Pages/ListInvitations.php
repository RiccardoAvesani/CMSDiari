<?php

namespace App\Filament\Resources\Invitations\Pages;

use App\Filament\Exports\InvitationExporter;
use App\Filament\Resources\Invitations\InvitationResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;

class ListInvitations extends ListRecords
{
    protected static string $resource = InvitationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ExportAction::make()
                ->label('Esporta')
                ->icon('heroicon-o-arrow-down-tray')
                ->exporter(InvitationExporter::class),
        ];
    }
}
