<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Filament\Resources\Pages\PageResource;
use App\Models\Page;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewPage extends ViewRecord
{
    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back_to_list')
                ->label('Torna alla Lista')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn(): string => PageResource::getUrl('index')),

            EditAction::make()
                ->visible(fn(): bool => PageResource::canEdit($this->record)),

            Action::make('soft_delete')
                ->label('Elimina')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn(): bool => PageResource::canDelete($this->record) && ! $this->record->isDeleted)
                ->action(function (): void {
                    /** @var Page $record */
                    $record = $this->record;

                    $record->softDelete();

                    Notification::make()
                        ->title('Pagina eliminata')
                        ->success()
                        ->send();

                    $this->redirect(PageResource::getUrl('index'));
                }),

            Action::make('restore')
                ->label('Ripristina')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn(): bool => User::canAdminOrInternal() && $this->record->isDeleted)
                ->action(function (): void {
                    /** @var Page $record */
                    $record = $this->record;

                    $record->restore();

                    Notification::make()
                        ->title('Pagina ripristinata')
                        ->success()
                        ->send();

                    $this->redirect(PageResource::getUrl('index'));
                }),
        ];
    }
}
