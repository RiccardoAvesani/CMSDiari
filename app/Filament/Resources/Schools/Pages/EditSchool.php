<?php

namespace App\Filament\Resources\Schools\Pages;

use App\Filament\Resources\Schools\SchoolResource;
use App\Models\School;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditSchool extends EditRecord
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

        $this->redirect(SchoolResource::getUrl('index'));
    }
}
