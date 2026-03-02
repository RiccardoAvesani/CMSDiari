<?php

namespace App\Filament\Resources\Invitations\Pages;

use App\Filament\Resources\Invitations\InvitationResource;
use App\Models\Invitation;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditInvitation extends EditRecord
{
    protected static string $resource = InvitationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back_to_list')
                ->label('Torna alla Lista')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn(): string => InvitationResource::getUrl('index')),

            Action::make('soft_delete')
                ->label('Elimina')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn(): bool => InvitationResource::canDelete($this->record) && (($this->record->status ?? null) !== Invitation::STATUS_DELETED))
                ->action(function (): void {
                    $this->record->update(['status' => Invitation::STATUS_DELETED]);

                    Notification::make()
                        ->title('Invito eliminato')
                        ->success()
                        ->send();

                    $this->redirect(InvitationResource::getUrl('index'));
                }),

            Action::make('restore')
                ->label('Ripristina')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn(): bool => User::canAdminOrInternal() && (($this->record->status ?? null) === Invitation::STATUS_DELETED))
                ->action(function (): void {
                    $this->record->update(['status' => $this->getRestoredStatus($this->record)]);

                    Notification::make()
                        ->title('Invito ripristinato')
                        ->success()
                        ->send();

                    $this->redirect(InvitationResource::getUrl('index'));
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

        $this->redirect(InvitationResource::getUrl('index'));
    }

    private function getRestoredStatus(Invitation $record): string
    {
        if (! empty($record->user_id)) {
            return Invitation::STATUS_ACTIVE;
        }

        if (! empty($record->registered_at)) {
            return Invitation::STATUS_REGISTERED;
        }

        if (! empty($record->sent_at)) {
            return Invitation::STATUS_INVITED;
        }

        return Invitation::STATUS_READY;
    }
}
