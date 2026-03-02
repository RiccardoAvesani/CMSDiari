<?php

namespace App\Filament\Resources\Templates\Pages;

use App\Actions\Templates\CreateAdditionalPageForTemplate;
use App\Actions\Templates\RegeneratePagesForTemplate;
use App\Filament\Resources\Templates\TemplateResource;
use App\Filament\Resources\Pages\PageResource;
use App\Models\Page;
use App\Models\Template;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Radio;
use Filament\Schemas\Components\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;
use Throwable;

class ViewTemplate extends ViewRecord
{
    protected static string $resource = TemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back_to_list')
                ->label('Torna alla Lista')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn(): string => TemplateResource::getUrl('index')),

            Action::make('add_page')
                ->label('Aggiungi Pagina')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->schema([
                    Select::make('page_type_id')
                        ->label('Tipologia Pagina')
                        ->options(fn(): array => $this->getAllowedPageTypeOptions())
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, Form $form): void {
                            $pageTypeId = is_numeric($state) ? (int) $state : null;

                            $stateAll = $form->getState();
                            $stateAll['position'] = $this->suggestedPositionForAdditionalPage($pageTypeId);

                            $form->fill($stateAll);
                        }),

                    TextInput::make('position')
                        ->label('Posizione')
                        ->numeric()
                        ->minValue(1)
                        ->required()
                        ->default(fn(): int => $this->suggestedPositionForAdditionalPage(null)),

                    TextInput::make('description')
                        ->label('Nome Pagina (opzionale)')
                        ->maxLength(255)
                        ->nullable(),
                ])
                ->action(function (array $data): void {
                    try {
                        /** @var Template $template */
                        $template = $this->record;

                        $pageTypeId = (int) ($data['page_type_id'] ?? 0);
                        $position = (int) ($data['position'] ?? 0);
                        $description = $data['description'] ?? null;

                        $page = app(CreateAdditionalPageForTemplate::class)->handle(
                            template: $template,
                            pageTypeId: $pageTypeId,
                            position: $position,
                            description: $description,
                        );

                        Notification::make()
                            ->title('Pagina aggiunta')
                            ->success()
                            ->send();

                        $this->redirect(PageResource::getUrl('edit', ['record' => $page->id]));
                    } catch (Throwable $e) {
                        Notification::make()
                            ->title('Errore')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('regenerate_pages')
                ->label('Rigenera Pagine')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn(): bool => User::canAdminOrInternal())
                ->schema([
                    Radio::make('regeneration_mode')
                        ->label('Modalità rigenerazione')
                        ->options([
                            RegeneratePagesForTemplate::REGENERATION_MODE_PURGE => 'Pulisci i dati di tutte le Pagine, reinstanziandone 1 per Tipologia',
                            RegeneratePagesForTemplate::REGENERATION_MODE_ADD_ONLY => 'Lascia intatte le Pagine attuali, senza eliminare le eccedenti ed aggiungendone 1 per ogni nuova Tipologia',
                            RegeneratePagesForTemplate::REGENERATION_MODE_ADD_AND_TRIM => 'Lascia intatte le Pagine attuali, eliminando le eccedenti ed aggiungendone 1 per ogni nuova Tipologia',
                        ])
                        ->default(RegeneratePagesForTemplate::REGENERATION_MODE_PURGE)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    try {
                        /** @var Template $template */
                        $template = $this->record;

                        app(RegeneratePagesForTemplate::class)->handle(
                            template: $template,
                            regenerationMode: (string) ($data['regeneration_mode'] ?? RegeneratePagesForTemplate::REGENERATION_MODE_PURGE),
                        );

                        Notification::make()
                            ->title('Pagine rigenerate')
                            ->success()
                            ->send();

                        $this->redirect(TemplateResource::getUrl('view', ['record' => $template->id]));
                    } catch (Throwable $e) {
                        Notification::make()
                            ->title('Errore')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('open_pages')
                ->label('Compila')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->url(fn(): string => PageResource::getUrl('index', [
                    'tableFilters[template_id][value]' => $this->record->id,
                ])),

            EditAction::make()
                ->visible(fn(): bool => TemplateResource::canEdit($this->record)),

            Action::make('soft_delete')
                ->label('Elimina')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn(): bool => TemplateResource::canDelete($this->record) && ($this->record->status ?? null) !== Template::STATUS_DELETED)
                ->action(function (): void {
                    /** @var Template $record */
                    $this->record->updateStatus(Template::STATUS_DELETED);

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
                ->visible(fn(): bool => User::canAdminOrInternal() && ($this->record->status ?? null) === Template::STATUS_DELETED)
                ->action(function (): void {
                    /** @var Template $record */
                    $this->record->updateStatus(Template::STATUS_ACTIVE);

                    Notification::make()
                        ->title('Modello Compilato ripristinato')
                        ->success()
                        ->send();

                    $this->redirect(TemplateResource::getUrl('index'));
                }),
        ];
    }

    private function getAllowedPageTypeOptions(): array
    {
        /** @var Template $template */
        $template = $this->record;

        $template->loadMissing('templateType.items.pageType');

        $items = $template->templateType?->items ?? collect();

        return $items
            ->map(function ($item): array {
                $id = (int) ($item->page_type_id ?? 0);
                $label = (string) ($item->pageType?->description ?? ('Tipologia ' . $id));

                return [$id => $label];
            })
            ->collapse()
            ->filter(fn($label, $id) => (int) $id > 0)
            ->all();
    }

    private function suggestedPositionForAdditionalPage(?int $pageTypeId): int
    {
        /** @var Template $template */
        $template = $this->record;

        if (! $template?->id) {
            return 1;
        }

        if ($pageTypeId) {
            $last = Page::query()
                ->where('template_id', $template->id)
                ->where('page_type_id', $pageTypeId)
                ->max('position');

            if (is_numeric($last) && (int) $last > 0) {
                return ((int) $last) + 1;
            }

            $template->loadMissing('templateType.items');

            $fallback = (int) ($template->templateType?->items?->firstWhere('page_type_id', $pageTypeId)?->position ?? 1);

            return $fallback > 0 ? $fallback : 1;
        }

        $maxAny = Page::query()
            ->where('template_id', $template->id)
            ->max('position');

        if (is_numeric($maxAny) && (int) $maxAny > 0) {
            return ((int) $maxAny) + 1;
        }

        return 1;
    }
}
