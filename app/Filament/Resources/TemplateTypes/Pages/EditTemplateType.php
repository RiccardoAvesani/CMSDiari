<?php

namespace App\Filament\Resources\TemplateTypes\Pages;

use App\Filament\Resources\TemplateTypes\TemplateTypeResource;
use App\Models\TemplateType;
use App\Models\PageType;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use Filament\Actions\Action;
use App\Structures\TemplateTypeStructureComposer;

class EditTemplateType extends EditRecord
{
    protected static string $resource = TemplateTypeResource::class;
    public string $structureEditorMode = 'html';
    public ?string $structureJsonError = null;
    protected bool $isSyncingItems = false;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back_to_list')
                ->label('Torna alla Lista')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn(): string => TemplateTypeResource::getUrl('index')),

            Action::make('soft_delete')
                ->label('Elimina')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn(): bool => TemplateTypeResource::canDelete($this->record) && (($this->record->status ?? null) !== TemplateType::STATUS_DELETED))
                ->action(function (): void {
                    /** @var TemplateType $record */
                    $record = $this->record;

                    $record->updateStatus(TemplateType::STATUS_DELETED);

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
                ->visible(fn(): bool => User::canAdminOrInternal() && (($this->record->status ?? null) === TemplateType::STATUS_DELETED))
                ->action(function (): void {
                    /** @var TemplateType $record */
                    $record = $this->record;

                    $record->updateStatus(TemplateType::STATUS_ACTIVE);

                    Notification::make()
                        ->title('Modello Generico ripristinato')
                        ->success()
                        ->send();

                    $this->redirect(TemplateTypeResource::getUrl('index'));
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

        $this->redirect(TemplateTypeResource::getUrl('index'));
    }

    public function syncItemsPositionsAndSort(): void
    {
        if ($this->isSyncingItems) {
            return;
        }

        $this->isSyncingItems = true;

        $state = $this->form->getState();
        $items = is_array($state['items'] ?? null) ? $state['items'] : [];

        $items = $this->normalizeItems($items);

        $state['items'] = $items;
        $this->form->fill($state);

        $this->isSyncingItems = false;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $items = is_array($data['items'] ?? null) ? $data['items'] : [];
        $data['items'] = $this->normalizeItems($items);

        return $data;
    }

    protected function beforeSave(): void
    {
        $this->enforceMaxPagesLimit();
        $this->enforcePageTypeMaxPagesLimit();
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data = $this->enforceFinaleFlags($data);

        $items = is_array($data['items'] ?? null) ? $data['items'] : [];
        $items = $this->normalizeItems($items);

        $data['items'] = $items;
        $data['structure'] = TemplateTypeStructureComposer::composeFromItems($items);

        return $data;
    }

    private function normalizeItems(array $items): array
    {
        $clean = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $clean[] = $item;
        }

        $positions = array_map(
            fn(array $i): int => (int) ($i['position'] ?? 0),
            $clean,
        );

        $positions = array_values(array_filter($positions, fn(int $p): bool => $p > 0));
        sort($positions);

        $used = [];
        $pool = [];

        foreach ($positions as $p) {
            if (isset($used[$p])) {
                continue;
            }

            $used[$p] = true;
            $pool[] = $p;
        }

        $max = empty($pool) ? 0 : max($pool);

        while (count($pool) < count($clean)) {
            $max++;

            if (isset($used[$max])) {
                continue;
            }

            $used[$max] = true;
            $pool[] = $max;
        }

        foreach ($clean as $idx => $item) {
            $item['position'] = $pool[$idx] ?? ($idx + 1);
            $item['sort'] = $idx + 1;
            $clean[$idx] = $item;
        }

        usort($clean, function (array $a, array $b): int {
            return ((int) ($a['position'] ?? 0)) <=> ((int) ($b['position'] ?? 0));
        });

        foreach ($clean as $i => $item) {
            $item['sort'] = $i + 1;
            $clean[$i] = $item;
        }

        return $clean;
    }

    private function enforceFinaleFlags(array $data): array
    {
        $isCustomFinale = (bool) ($data['is_custom_finale'] ?? false);

        if (! $isCustomFinale) {
            $data['is_giustificazioni'] = false;
            $data['is_permessi'] = false;
            $data['is_visite'] = false;
        } else {
            $data['is_giustificazioni'] = (bool) ($data['is_giustificazioni'] ?? false);
            $data['is_permessi'] = (bool) ($data['is_permessi'] ?? false);
            $data['is_visite'] = (bool) ($data['is_visite'] ?? false);
        }

        return $data;
    }

    private function enforceMaxPagesLimit(): void
    {
        $state = $this->form->getState();

        $maxPages = (int) ($state['max_pages'] ?? 0);
        $items = $state['items'] ?? [];
        $itemsCount = is_array($items) ? count($items) : 0;

        if ($maxPages > 0 && $itemsCount > $maxPages) {
            Notification::make()
                ->title('Hai aggiunto un numero di Pagine personalizzabili superiore al numero massimo per questo Modello')
                ->danger()
                ->send();

            throw new Halt();
        }
    }

    private function enforcePageTypeMaxPagesLimit(): void
    {
        $state = $this->form->getState();

        $items = $state['items'] ?? null;

        if (! is_array($items)) {
            return;
        }

        $counts = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $pageTypeId = $item['page_type_id'] ?? null;

            if (! is_numeric($pageTypeId)) {
                continue;
            }

            $pageTypeId = (int) $pageTypeId;

            if (! isset($counts[$pageTypeId])) {
                $counts[$pageTypeId] = 0;
            }

            $counts[$pageTypeId]++;
        }

        if ($counts === []) {
            return;
        }

        $pageTypes = PageType::query()
            ->whereIn('id', array_keys($counts))
            ->get()
            ->keyBy('id');

        $over = [];

        foreach ($counts as $pageTypeId => $count) {
            /** @var PageType|null $pageType */
            $pageType = $pageTypes->get($pageTypeId);

            $max = max(1, (int) ($pageType?->max_pages ?? 1));

            if ($count <= $max) {
                continue;
            }

            $label = trim((string) ($pageType?->description ?? ('Tipologia ' . $pageTypeId)));
            $over[] = $label . ' (' . $count . '/' . $max . ')';
        }

        if ($over !== []) {
            Notification::make()
                ->title('Numero massimo occorrenze superato')
                ->body('Hai superato il Numero massimo per: ' . implode(', ', $over))
                ->danger()
                ->send();

            throw new Halt();
        }
    }
}
