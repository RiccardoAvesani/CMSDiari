<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back_to_list')
                ->label('Torna alla Lista')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn(): string => UserResource::getUrl('index')),

            EditAction::make()
                ->visible(fn(): bool => UserResource::canEdit($this->record)),

            Action::make('soft_delete')
                ->label('Elimina')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn(): bool => UserResource::canDelete($this->record) && ! $this->record->isDeleted)
                ->action(function (): void {
                    $this->record->softDelete();

                    Notification::make()
                        ->title('Utente eliminato')
                        ->success()
                        ->send();

                    $this->redirect(UserResource::getUrl('index'));
                }),

            Action::make('restore')
                ->label('Ripristina')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn(): bool => User::canAdminOrInternal() && $this->record->isDeleted)
                ->action(function (): void {
                    $this->record->restore();

                    Notification::make()
                        ->title('Utente ripristinato')
                        ->success()
                        ->send();

                    $this->redirect(UserResource::getUrl('index'));
                }),
        ];
    }
}