<?php

namespace App\Filament\Resources\PageTypes\Pages;

use App\Filament\Resources\PageTypes\PageTypeResource;
use App\Models\PageType;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewPageType extends ViewRecord
{
    protected static string $resource = PageTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back_to_list')
                ->label('Torna alla Lista')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn(): string => PageTypeResource::getUrl('index')),

            EditAction::make()
                ->visible(fn(): bool => PageTypeResource::canEdit($this->record)),

            Action::make('soft_delete')
                ->label('Elimina')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn(): bool => PageTypeResource::canDelete($this->record) && (($this->record->status ?? null) !== PageType::STATUS_DELETED))
                ->action(function (): void {
                    $this->record->softDelete();

                    Notification::make()
                        ->title('Tipologia Pagina eliminata')
                        ->success()
                        ->send();

                    $this->redirect(PageTypeResource::getUrl('index'));
                }),

            Action::make('restore')
                ->label('Ripristina')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn(): bool => User::canAdminOrInternal() && (($this->record->status ?? null) === PageType::STATUS_DELETED))
                ->action(function (): void {
                    $this->record->restore();

                    Notification::make()
                        ->title('Tipologia Pagina ripristinata')
                        ->success()
                        ->send();

                    $this->redirect(PageTypeResource::getUrl('index'));
                }),
        ];
    }
}