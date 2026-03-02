<?php

namespace App\Filament\Resources\TemplateTypes\Schemas;

use App\Filament\Support\AuditSection;
use App\Models\TemplateType;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TemplateTypeInfolist
{
    public static function configureSchema(Schema $schema): Schema
    {
        return $schema->components([
            Section::make("Dettagli Modello")
                ->columns(4)
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('description')
                        ->label('Nome')
                        ->placeholder('-')
                        ->columnSpan(1),

                    TextEntry::make('size')
                        ->label('Taglia')
                        ->placeholder('-')
                        ->columnSpan(1),

                    TextEntry::make('max_pages')
                        ->label('Numero di Pagine personalizzabili')
                        ->numeric()
                        ->placeholder('-')
                        ->columnSpan(2),

                    IconEntry::make('is_custom_finale')
                        ->label('Sezione finale')
                        ->boolean()
                        ->columnSpanFull(),

                    IconEntry::make('is_giustificazioni')
                        ->label('Giustificazioni')
                        ->boolean()
                        ->columnSpan(1),

                    IconEntry::make('is_permessi')
                        ->label('Permessi')
                        ->boolean()
                        ->columnSpan(1),

                    IconEntry::make('is_visite')
                        ->label('Visite / Sicurezza / Ricevuta')
                        ->boolean()
                        ->columnSpan(2),
                ]),

            Section::make('Tipologie Pagina')
                ->columns(1)
                ->columnSpanFull()
                ->schema([
                    ViewEntry::make('template_type_items_render')
                        ->hiddenLabel()
                        ->columnSpanFull()
                        ->view('filament.templates.template-type-items')
                        ->viewData(fn(?TemplateType $record): array => [
                            'items' => static::getTemplateTypeItemsForView($record),
                        ]),
                ]),

            Section::make("Struttura")
                ->columns(1)
                ->columnSpanFull()
                ->schema([
                    ViewEntry::make('structure_render')
                        ->hiddenLabel()
                        ->view('filament.structures.structure-toggle')
                        ->viewData([
                            'structure' => fn(TemplateType $record): mixed => $record->structure,
                            'constraints' => fn(TemplateType $record): ?array => is_array($record->constraints) ? $record->constraints : null,
                            'editableValues' => false,
                            'hideValues' => true,
                        ])
                        ->columnSpanFull(),
                ]),

            AuditSection::make(
                statusLabel: 'Stato',
                statusLabels: TemplateType::statusLabels(),
            ),
        ]);
    }

    public static function getTemplateTypeItemsForView(?TemplateType $record): array
    {
        if (! $record) {
            return [];
        }

        $record->loadMissing('pageTypes');

        return $record->items
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
