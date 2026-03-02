<?php

namespace App\Filament\Resources\Invitations\Pages;

use App\Filament\Resources\Invitations\InvitationResource;
use App\Models\Invitation;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewInvitation extends ViewRecord
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

            EditAction::make()
                ->visible(fn(): bool => InvitationResource::canEdit($this->record)),

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
}
