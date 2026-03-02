<?php

namespace App\Filament\Resources\Templates\Pages;

use App\Filament\Resources\Templates\TemplateResource;
use App\Models\Template;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditTemplate extends EditRecord
{
    protected static string $resource = TemplateResource::class;
    public string $structureEditorMode = 'html';
    public ?string $structureJsonError = null;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back_to_list')
                ->label('Torna alla Lista')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn(): string => TemplateResource::getUrl('index')),

            Action::make('soft_delete')
                ->label('Elimina')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn(): bool => TemplateResource::canDelete($this->record) && (($this->record->status ?? null) !== Template::STATUS_DELETED))
                ->action(function (): void {
                    /** @var Template $record */
                    $record = $this->record;

                    $record->updateStatus(Template::STATUS_DELETED);

                    Notification::make()
                        ->title('Modello Compilato eliminato')
                        ->success()
                        ->send();

                    $this->redirect(TemplateResource::getUrl('index'));
                }),

            Action::make('restore')
                ->label('Ripristina')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn(): bool => User::canAdminOrInternal() && (($this->record->status ?? null) === Template::STATUS_DELETED))
                ->action(function (): void {
                    /** @var Template $record */
                    $record = $this->record;

                    $record->updateStatus(Template::STATUS_ACTIVE);

                    Notification::make()
                        ->title('Modello Compilato ripristinato')
                        ->success()
                        ->send();

                    $this->redirect(TemplateResource::getUrl('index'));
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

    public function canEditCompiledValues(): bool
    {
        /** @var Template $template */
        $template = $this->record;

        return $this->canCollectDataNow($template->order);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var Template $template */
        $template = $this->record;

        $data['template_type_id'] = $template->template_type_id;
        $data['order_id'] = $template->order_id;
        $data['school_id'] = $template->school_id;

        $data['structure'] = $template->structure;
        $data['constraints'] = $template->constraints;
        $data['size'] = $template->size;
        $data['max_pages'] = $template->max_pages;

        $data['is_custom_finale'] = $template->is_custom_finale;
        $data['is_giustificazioni'] = $template->is_giustificazioni;
        $data['is_permessi'] = $template->is_permessi;
        $data['is_visite'] = $template->is_visite;

        return $data;
    }
}
