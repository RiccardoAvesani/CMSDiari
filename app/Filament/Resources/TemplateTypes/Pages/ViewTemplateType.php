<?php

namespace App\Filament\Resources\TemplateTypes\Pages;

use App\Filament\Resources\TemplateTypes\TemplateTypeResource;
use App\Models\TemplateType;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewTemplateType extends ViewRecord
{
    protected static string $resource = TemplateTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back_to_list')
                ->label('Torna alla Lista')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn(): string => TemplateTypeResource::getUrl('index')),

            EditAction::make()
                ->visible(fn(): bool => TemplateTypeResource::canEdit($this->record)),

            Action::make('soft_delete')
                ->label('Elimina')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn(): bool => TemplateTypeResource::canDelete($this->record) && ($this->record->status ?? null) !== TemplateType::STATUS_DELETED)
                ->action(function (): void {
                    $this->record->updateStatus(TemplateType::STATUS_DELETED);

                    Notification::make()
                        ->title('Modello Generico eliminato')
                        ->success()
                        ->send();

                    $this->redirect(TemplateTypeResource::getUrl('index'));
                }),

            Action::make('restore')
                ->label('Ripristina')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn(): bool => User::canAdminOrInternal() && ($this->record->status ?? null) === TemplateType::STATUS_DELETED)
                ->action(function (): void {
                    $this->record->updateStatus(TemplateType::STATUS_ACTIVE);

                    Notification::make()
                        ->title('Modello Generico ripristinato')
                        ->success()
                        ->send();

                    $this->redirect(TemplateTypeResource::getUrl('index'));
                }),
        ];
    }
}
