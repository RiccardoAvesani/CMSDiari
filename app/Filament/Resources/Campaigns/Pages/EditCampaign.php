<?php

namespace App\Filament\Resources\Campaigns\Pages;

use App\Filament\Resources\Campaigns\CampaignResource;
use App\Models\Campaign;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditCampaign extends EditRecord
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

            Action::make('soft_delete')
                ->label('Elimina')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn(): bool => CampaignResource::canDelete($this->record) && ($this->record->status !== Campaign::STATUS_DELETED))
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

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            Action::make('save_and_exit')
                ->label('Salva ed esci')
                ->color('gray')
                ->action('saveAndExit'),
            $this->getCancelFormAction(),
        ];
    }

    public function saveAndExit(): void
    {
        $this->save();

        $this->redirect(CampaignResource::getUrl('index'));
    }
}
