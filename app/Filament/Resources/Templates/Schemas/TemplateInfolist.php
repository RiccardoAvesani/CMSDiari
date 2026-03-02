<?php

namespace App\Filament\Resources\Templates\Schemas;

use App\Filament\Support\AuditSection;
use App\Models\Template;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TemplateInfolist
{
    public static function configureSchema(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Dettagli Modello')
                ->columns(5)
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('description')
                        ->label('Nome')
                        ->placeholder('-')
                        ->columnSpan(1),

                    TextEntry::make('school.description')
                        ->label('Scuola')
                        ->placeholder('-')
                        ->columnSpan(2),

                    TextEntry::make('templateType.description')
                        ->label('Modello Generico')
                        ->placeholder('-')
                        ->columnSpan(1),

                    TextEntry::make('order.external_id')
                        ->label('Ordine')
                        ->placeholder('-')
                        ->columnSpan(1),

                    IconEntry::make('is_custom_finale')
                        ->label('Sezione finale')
                        ->boolean()
                        ->visible(fn(Template $record): bool => (bool) ($record->is_custom_finale ?? false))
                        ->columnSpanFull(),

                    IconEntry::make('is_giustificazioni')
                        ->label('Giustificazioni')
                        ->boolean()
                        ->visible(fn(Template $record): bool => (bool) ($record->is_custom_finale ?? false))
                        ->columnSpan(1),

                    IconEntry::make('is_permessi')
                        ->label('Permessi')
                        ->boolean()
                        ->visible(fn(Template $record): bool => (bool) ($record->is_custom_finale ?? false))
                        ->columnSpan(1),

                    IconEntry::make('is_visite')
                        ->label('Visite Sicurezza Ricevuta')
                        ->boolean()
                        ->visible(fn(Template $record): bool => (bool) ($record->is_custom_finale ?? false))
                        ->columnSpan(3),
                ]),

                Section::make('Tipologie Pagina')
                    ->columns(1)
                    ->columnSpanFull()
                    ->schema([
                        ViewEntry::make('template_type_items_render')
                            ->hiddenLabel()
                            ->columnSpanFull()
                            ->view('filament.templates.template-type-items')
                            ->viewData(fn(?Template $record): array => [
                                'items' => static::getTemplateTypeItemsForView($record),
                            ]),
                    ]),

                Section::make("Struttura")
                    ->columns(1)
                    ->columnSpanFull()
                    ->schema([
                        ViewEntry::make('structure_render')
                            ->hiddenLabel()
                            ->columnSpanFull()
                            ->view('filament.structures.structure-toggle')
                            ->viewData([
                                'structure' => fn(?Template $record): mixed => $record?->structure,
                                'constraints' => fn(?Template $record): ?array => is_array($record?->constraints) ? $record->constraints : null,
                                'editableValues' => false,
                                'hideValues' => true,
                            ]),
                    ]),

                AuditSection::make(
                    statusLabel: 'Stato',
                    statusLabels: Template::statusLabels(),
                ),
        ]);
    }

    public static function getTemplateTypeItemsForView(?Template $record): array
    {
        if (! $record) {
            return [];
        }

        $record->loadMissing('templateType.items.pageType');

        $templateType = $record->templateType;

        if (! $templateType) {
            return [];
        }

        return $templateType->items
            ->sortBy(fn($item) => (int) ($item->position ?? 0))
            ->map(function ($item): array {
                $pageType = $item->pageType;

                return [
                    'position' => (int) ($item->position ?? 0),
                    'page_type_id' => (int) ($item->page_type_id ?? 0),
                    'page_type_description' => (string) ($pageType?->description ?? 'Tipologia'),
                    'page_type_max_pages' => (int) (($pageType?->max_pages ?? 1) ?: 1),
                ];
            })
            ->values()
            ->all();
    }
}
