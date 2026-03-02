<?php

namespace App\Filament\Resources\Schools\Pages;

use App\Filament\Resources\Schools\SchoolResource;
use App\Models\School;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewSchool extends ViewRecord
{
    protected static string $resource = SchoolResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back_to_list')
                ->label('Torna alla Lista')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn(): string => SchoolResource::getUrl('index')),

            EditAction::make()
                ->visible(fn(): bool => SchoolResource::canEdit($this->record)),

            Action::make('soft_delete')
                ->label('Elimina')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn(): bool => SchoolResource::canDelete($this->record) && (($this->record->status ?? null) !== School::STATUS_DELETED))
                ->action(function (): void {
                    $this->record->update(['status' => School::STATUS_DELETED]);

                    Notification::make()
                        ->title('Scuola eliminata')
                        ->success()
                        ->send();

                    $this->redirect(SchoolResource::getUrl('index'));
                }),

            Action::make('restore')
                ->label('Ripristina')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn(): bool => User::canAdminOrInternal() && (($this->record->status ?? null) === School::STATUS_DELETED))
                ->action(function (): void {
                    $this->record->update(['status' => School::STATUS_ACTIVE]);

                    Notification::make()
                        ->title('Scuola ripristinata')
                        ->success()
                        ->send();

                    $this->redirect(SchoolResource::getUrl('index'));
                }),
        ];
    }
}
