<?php

namespace App\Filament\Resources\Locations\Pages;

use App\Filament\Resources\Locations\LocationResource;
use App\Models\Location;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewLocation extends ViewRecord
{
    protected static string $resource = LocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back_to_list')
                ->label('Torna alla Lista')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn(): string => LocationResource::getUrl('index')),

            EditAction::make()
                ->visible(fn(): bool => LocationResource::canEdit($this->record)),

            Action::make('soft_delete')
                ->label('Elimina')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn(): bool => LocationResource::canDelete($this->record) && !$this->record->isDeleted())
                ->action(function (): void {
                    /** @var Location $record */
                    $record = $this->record;
                    $record->softDelete();

                    Notification::make()
                        ->title('Sede eliminata')
                        ->success()
                        ->send();

                    $this->redirect(LocationResource::getUrl('index'));
                }),

            Action::make('restore')
                ->label('Ripristina')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn(): bool => User::canAdminOrInternal() && $this->record->isDeleted())
                ->action(function (): void {
                    /** @var Location $record */
                    $record = $this->record;
                    $record->restore();

                    Notification::make()
                        ->title('Sede ripristinata')
                        ->success()
                        ->send();

                    $this->redirect(LocationResource::getUrl('index'));
                }),
        ];
    }
}
