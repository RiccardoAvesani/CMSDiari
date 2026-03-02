<?php

namespace App\Filament\Resources\PageTypes\Pages;

use App\Filament\Resources\PageTypes\PageTypeResource;
use App\Models\PageType;
use App\Models\User;
use App\Structures\StructureBuilderMapper;
use App\Structures\StructureJson;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Facades\Auth;

class EditPageType extends EditRecord
{
    protected static string $resource = PageTypeResource::class;
    public string $structureEditorMode = 'html';
    public ?string $structureJsonError = null;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back_to_list')
                ->label('Torna alla Lista')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn(): string => PageTypeResource::getUrl('index')),

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

        $this->redirect(PageTypeResource::getUrl('index'));
    }

    public function toggleStructureEditorMode(): void
    {
        $this->structureJsonError = null;

        $state = $this->form->getRawState();

        if (($this->structureEditorMode ?? 'html') === 'html') {
            $title = $this->resolvePageTypeStructureTitle($state);

            $fields = is_array($state['structure_fields'] ?? null)
                ? $state['structure_fields']
                : [];

            $structure = StructureBuilderMapper::fieldsToStructure(
                fields: $fields,
                title: $title,
            );

            $this->form->fill([
                'structure_json' => StructureJson::encodePretty($structure),
            ]);

            $this->structureEditorMode = 'json';

            return;
        }

        $decoded = StructureJson::decode($state['structure_json'] ?? null);

        if (! ($decoded['ok'] ?? false)) {
            $this->structureJsonError = 'JSON non valido. ' . (($decoded['error'] ?? null) ?: 'Errore sconosciuto');

            return;
        }

        $decodedValue = $decoded['value'] ?? null;

        if (! is_array($decodedValue)) {
            $this->structureJsonError = 'JSON non valido: la Struttura deve essere un vettore.';

            return;
        }

        if (StructureBuilderMapper::containsNonEmptyValues($decodedValue)) {
            $this->structureJsonError = 'Nella Tipologia Pagina non è consentito valorizzare i campi "value".';

            return;
        }

        $sanitized = StructureBuilderMapper::normalizeDefinitionStructure($decodedValue);
        $fields = StructureBuilderMapper::structureToFields($sanitized);

        $this->form->fill([
            'structure_fields' => $fields,
        ]);

        $this->structureEditorMode = 'html';
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $structure = $data['structure'] ?? null;
        $fields = StructureBuilderMapper::structureToFields($structure);

        $data['structure_fields'] = $fields;

        $title = trim((string) ($data['description'] ?? ''));
        if ($title === '') {
            $title = 'Tipologia Pagina';
        }

        $data['structure_json'] = StructureJson::encodePretty(
            StructureBuilderMapper::fieldsToStructure($fields, $title)
        );

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! User::canAdminOrInternal()) {
            $data['structure'] = $this->record->structure;
            unset($data['structure_fields'], $data['structure_json']);

            return $data;
        }

        if (($this->structureEditorMode ?? 'html') === 'json') {
            $decoded = StructureJson::decode($data['structure_json'] ?? null);

            if (! ($decoded['ok'] ?? false) || ! is_array($decoded['value'] ?? null)) {
                Notification::make()
                    ->title('JSON Struttura non valido')
                    ->body(($decoded['error'] ?? null) ?: 'Verifica la sintassi del JSON.')
                    ->danger()
                    ->send();

                throw new Halt();
            }

            $decodedValue = $decoded['value'];

            if (StructureBuilderMapper::containsNonEmptyValues($decodedValue)) {
                Notification::make()
                    ->title('JSON non valido')
                    ->body('Nella Tipologia Pagina non è consentito valorizzare i campi "value".')
                    ->danger()
                    ->send();

                throw new Halt();
            }

            $data['structure'] = StructureBuilderMapper::normalizeDefinitionStructure($decodedValue);
        } else {
            $title = trim((string) ($data['description'] ?? $this->record->description ?? ''));
            if ($title === '') {
                $title = 'Tipologia Pagina';
            }

            $fields = is_array($data['structure_fields'] ?? null)
                ? $data['structure_fields']
                : [];

            $data['structure'] = StructureBuilderMapper::fieldsToStructure(
                fields: $fields,
                title: $title,
            );
        }

        unset($data['structure_fields'], $data['structure_json']);

        return $data;
    }

    private function resolvePageTypeStructureTitle(array $state): string
    {
        $title = trim((string) ($state['description'] ?? ''));

        if ($title !== '') {
            return $title;
        }

        $title = trim((string) ($this->record->description ?? ''));

        if ($title !== '') {
            return $title;
        }

        return 'Tipologia Pagina';
    }
}
