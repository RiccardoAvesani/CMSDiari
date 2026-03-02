<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
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

            Action::make('soft_delete')
                ->label('Elimina')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn(): bool => UserResource::canDelete($this->record) && ! $this->record->isDeleted())
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
                ->visible(fn(): bool => User::canAdminOrInternal() && $this->record->isDeleted())
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

        $this->redirect(UserResource::getUrl('index'));
    }
}
