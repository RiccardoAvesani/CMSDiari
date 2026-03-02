<?php

namespace App\Filament\Resources\Contacts\Pages;

use App\Filament\Resources\Contacts\ContactResource;
use App\Models\Contact;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewContact extends ViewRecord
{
    protected static string $resource = ContactResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back_to_list')
                ->label('Torna alla Lista')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn(): string => ContactResource::getUrl('index')),

            EditAction::make()
                ->visible(fn(): bool => ContactResource::canEdit($this->record)),

            Action::make('soft_delete')
                ->label('Elimina')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn(): bool => ContactResource::canDelete($this->record) && ! $this->record->isDeleted)
                ->action(function (): void {
                    /** @var Contact $record */
                    $record = $this->record;

                    $record->softDelete();

                    Notification::make()
                        ->title('Contatto eliminato')
                        ->success()
                        ->send();

                    $this->redirect(ContactResource::getUrl('index'));
                }),

            Action::make('restore')
                ->label('Ripristina')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn(): bool => User::canAdminOrInternal() && $this->record->isDeleted)
                ->action(function (): void {
                    /** @var Contact $record */
                    $record = $this->record;

                    $record->restore();

                    Notification::make()
                        ->title('Contatto ripristinato')
                        ->success()
                        ->send();

                    $this->redirect(ContactResource::getUrl('index'));
                }),
        ];
    }
}
