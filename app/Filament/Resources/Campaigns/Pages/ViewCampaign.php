<?php

namespace App\Filament\Resources\Campaigns\Pages;

use App\Filament\Resources\Campaigns\CampaignResource;
use App\Models\Campaign;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewCampaign extends ViewRecord
{
    protected static string $resource = CampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back_to_list')
                ->label('Torna alla Lista')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn(): string => CampaignResource::getUrl('index')),

            EditAction::make()
                ->visible(fn(): bool => CampaignResource::canEdit($this->record)),

            Action::make('soft_delete')
                ->label('Elimina')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn(): bool => CampaignResource::canDelete($this->record) && $this->record->status !== Campaign::STATUS_DELETED)
                ->action(function (): void {
                    $this->record->update(['status' => Campaign::STATUS_DELETED]);

                    Notification::make()
                        ->title('Campagna eliminata')
                        ->success()
                        ->send();

                    $this->redirect(CampaignResource::getUrl('index'));
                }),

            Action::make('restore')
                ->label('Ripristina')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn(): bool => User::canAdminOrInternal() && (($this->record->status ?? null) === Campaign::STATUS_DELETED))
                ->action(function (): void {
                    $this->record->update(['status' => Campaign::STATUS_ACTIVE]);

                    Notification::make()
                        ->title('Campagna ripristinata')
                        ->success()
                        ->send();

                    $this->redirect(CampaignResource::getUrl('index'));
                }),
        ];
    }
}
