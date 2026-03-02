<?php

declare(strict_types=1);

namespace App\Filament\Resources\Pages\Pages;

use App\Filament\Resources\Pages\PageResource;
use App\Models\Page;
use App\Models\Order;
use App\Models\User;
use App\Structures\StructureValueMapper;
use App\Structures\StructureJson;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class EditPage extends EditRecord
{
    protected static string $resource = PageResource::class;
    public string $structureEditorMode = 'html';
    public ?string $structureJsonError = null;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back_to_list')
                ->label('Torna alla Lista')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn(): string => PageResource::getUrl('index')),

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

    public function canEditCompiledValues(): bool
    {
        if (User::canAdminOrInternal()) {
            return true;
        }

        /** @var Page $page */
        $page = $this->record;

        return $this->canCollectDataNow($page->order);
    }

    public function toggleStructureEditorMode(): void
    {
        $this->structureJsonError = null;

        if (($this->structureEditorMode ?? 'html') === 'html') {
            $state = $this->form->getRawState();

            /** @var Page $page */
            $page = $this->record;

            $baseStructure = $page->structure;

            $values = is_array($state['structure_values'] ?? null)
                ? $state['structure_values']
                : [];

            $merged = StructureValueMapper::applyValues($baseStructure, $values);

            $this->form->fill([
                'structure_json' => StructureJson::encodePretty($merged),
            ]);

            $this->structureEditorMode = 'json';

            return;
        }

        $state = $this->form->getRawState();

        $decoded = StructureJson::decode($state['structure_json'] ?? null);

        if (! ($decoded['ok'] ?? false) || ! is_array($decoded['value'] ?? null)) {
            $this->structureJsonError = 'JSON non valido. ' . (($decoded['error'] ?? null) ?: 'Errore sconosciuto');

            return;
        }

        /** @var Page $page */
        $page = $this->record;

        if (! StructureJson::onlyValuesChanged($page->structure, $decoded['value'])) {
            $this->structureJsonError = 'Nel JSON puoi modificare solo le proprietà "value".';

            return;
        }

        $values = StructureValueMapper::extractValues($decoded['value']);

        $this->form->fill([
            'structure_values' => $values,
        ]);

        $this->structureEditorMode = 'html';
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Page $page */
        $page = $this->record;

        $data['structure_json'] = StructureJson::encodePretty($page->structure);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            throw new RuntimeException('Utente non autenticato.');
        }

        /** @var Page $page */
        $page = $this->record;

        $isExternal = str_starts_with((string) ($user->role ?? ''), 'external');

        if ($isExternal && ! $this->canCollectDataNow($page->order)) {
            Notification::make()
                ->title('Raccolta dati chiusa')
                ->body('Non è possibile modificare i contenuti. Verifica lo stato dell’Ordine e la Scadenza di raccolta dati.')
                ->danger()
                ->send();

            throw new Halt();
        }

        if (($this->structureEditorMode ?? 'html') === 'json') {
            $decoded = StructureJson::decode($data['structure_json'] ?? null);

            if (! ($decoded['ok'] ?? false) || ! is_array($decoded['value'] ?? null)) {
                Notification::make()
                    ->title('JSON non valido')
                    ->body(($decoded['error'] ?? null) ?: 'Verifica la sintassi del JSON.')
                    ->danger()
                    ->send();

                throw new Halt();
            }

            if (! StructureJson::onlyValuesChanged($page->structure, $decoded['value'])) {
                Notification::make()
                    ->title('Modifica non consentita')
                    ->body('Nel JSON puoi modificare solo le proprietà "value".')
                    ->danger()
                    ->send();

                throw new Halt();
            }

            $data['structure'] = $decoded['value'];

            unset($data['structure_json'], $data['structure_values']);

            return $data;
        }

        $values = is_array($data['structure_values'] ?? null)
            ? $data['structure_values']
            : [];

        $baseStructure = $page->structure;

        $data['structure'] = StructureValueMapper::applyValues($baseStructure, $values);

        unset($data['structure_json'], $data['structure_values']);

        return $data;
    }

    private function canCollectDataNow(?Order $order): bool
    {
        return PageResource::canExternalEditValues($order);
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

        $this->redirect(PageResource::getUrl('index'));
    }
}
