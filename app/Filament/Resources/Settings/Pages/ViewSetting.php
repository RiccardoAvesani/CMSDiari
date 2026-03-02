<?php

namespace App\Filament\Resources\Settings\Pages;

use App\Filament\Resources\Settings\SettingResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewSetting extends ViewRecord
{
    protected static string $resource = SettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back_to_list')
                ->label('Torna alla Lista')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn(): string => SettingResource::getUrl('index')),

            EditAction::make()
                ->visible(fn(): bool => SettingResource::canEdit($this->record)),

            Action::make('soft_delete')
                ->label('Elimina')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn(): bool => SettingResource::canDelete($this->record) && ! $this->record->isDeleted())
                ->action(function (): void {
                    $this->record->softDelete();

                    Notification::make()
                        ->title('Impostazione eliminata')
                        ->success()
                        ->send();

                    $this->redirect(SettingResource::getUrl('index'));
                }),

            Action::make('restore')
                ->label('Ripristina')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn(): bool => User::canAdminOrInternal() && $this->record->isDeleted())
                ->action(function (): void {
                    $this->record->restore();

                    Notification::make()
                        ->title('Impostazione ripristinata')
                        ->success()
                        ->send();

                    $this->redirect(SettingResource::getUrl('index'));
                }),
        ];
    }
}
