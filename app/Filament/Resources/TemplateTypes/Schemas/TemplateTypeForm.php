<?php

declare(strict_types=1);

namespace App\Filament\Resources\TemplateTypes\Schemas;

use App\Filament\Support\AuditSection;
use App\Models\PageType;
use App\Models\TemplateType;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Livewire\Component as LivewireComponent;

class TemplateTypeForm
{
    public static function configureSchema(Schema $schema): Schema
    {
        return $schema->components([
            Section::make("Dettagli Modello")
                ->columns(4)
                ->columnSpanFull()
                ->schema([
                    Hidden::make('status')
                        ->default(TemplateType::STATUS_ACTIVE)
                        ->required(),

                    TextInput::make('description')
                        ->label('Nome')
                        ->maxLength(255)
                        ->nullable()
                        ->columnSpan(1),

                    Select::make('size')
                        ->label('Taglia')
                        ->options(TemplateType::sizeOptions())
                        ->default(TemplateType::SIZE_M)
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, LivewireComponent $livewire): void {
                            $data = $livewire->form->getState();
                            $data['constraints'] = TemplateType::defaultConstraintsForSize(is_string($state) ? $state : null);
                            $livewire->form->fill($data);
                        })
                        ->columnSpan(1),

                    TextInput::make('max_pages')
                        ->label('Numero Pagine personalizzabili')
                        ->numeric()
                        ->minValue(1)
                        ->required()
                        ->default(function (?TemplateType $record): int {
                            if ($record?->max_pages) {
                                return (int) $record->max_pages;
                            }

                            $existing = $record?->items?->count();

                            return max(1, (int) ($existing ?? 1));
                        })
                        ->columnSpan(1),

                    Hidden::make('constraints')
                        ->default(function (?TemplateType $record): array {
                            if (is_array($record?->constraints) && $record->constraints !== []) {
                                return $record->constraints;
                            }

                            return TemplateType::defaultConstraintsForSize(TemplateType::SIZE_M);
                        })
                        ->disabled()
                        ->columnSpan(1),

                    Toggle::make('is_custom_finale')
                        ->label('Sezione finale')
                        ->default(false)
                        ->live()
                        ->afterStateUpdated(function ($set, ?bool $state): void {
                            if ($state) {
                                return;
                            }

                            $set('is_giustificazioni', false);
                            $set('is_permessi', false);
                            $set('is_visite', false);
                        })
                        ->columnSpanFull(),

                    Toggle::make('is_giustificazioni')
                        ->label('Giustificazioni di assenza tot. 32')
                        ->default(false)
                        ->visible(fn($get): bool => (bool) ($get('is_custom_finale') ?? false))
                        ->dehydrated(fn($get): bool => (bool) ($get('is_custom_finale') ?? false))
                        ->columnSpan(1)
,
                    Toggle::make('is_permessi')
                        ->label('Permessi entrata/uscita tot. 16')
                        ->default(false)
                        ->visible(fn($get): bool => (bool) ($get('is_custom_finale') ?? false))
                        ->dehydrated(fn($get): bool => (bool) ($get('is_custom_finale') ?? false))
                        ->columnSpan(1),

                    Toggle::make('is_visite')
                        ->label('Benestare alle Visite guidate, Informativa Sicurezza e Ricevuta')
                        ->default(false)
                        ->visible(fn($get): bool => (bool) ($get('is_custom_finale') ?? false))
                        ->dehydrated(fn($get): bool => (bool) ($get('is_custom_finale') ?? false))
                        ->columnSpan(2),
                ]),

            Section::make('Tipologie Pagina')
                ->columns(1)
                ->columnSpanFull()
                ->hiddenLabel()
                ->schema([
                    Repeater::make('items')
                        ->hiddenLabel()
                        ->relationship()
                        ->default([])
                        ->live()
                        ->addActionLabel('Aggiungi Tipologia Pagina')
                        ->defaultItems(0)
                        ->orderColumn('sort')
                        ->reorderable()
                        ->afterStateUpdated(function ($state, LivewireComponent $livewire): void {
                            if (method_exists($livewire, 'syncItemsPositionsAndSort')) {
                                $livewire->syncItemsPositionsAndSort();
                            }
                        })
                        ->columns(7)
                        ->columnSpanFull()
                        ->schema([
                            TextInput::make('position')
                                ->label('Posizione')
                                ->numeric()
                                ->live()
                                ->minValue(1)
                                ->required()
                                ->columnSpan(3)
                                ->afterStateUpdated(function ($state, LivewireComponent $livewire): void {
                                    if (method_exists($livewire, 'syncItemsPositionsAndSort')) {
                                        $livewire->syncItemsPositionsAndSort();
                                    }
                                })
                                ->helperText('Indica la Posizione della prima Pagina di questa Tipologia nel Diario.'),

                            Select::make('page_type_id')
                                ->label('Tipologia Pagina')
                                ->columnSpan(4)
                                ->searchable()
                                ->live()
                                ->preload()
                                ->required()
                                ->relationship(
                                    name: 'pageType',
                                    titleAttribute: 'description',
                                    modifyQueryUsing: fn($query) => $query->where('status', '!=', PageType::STATUS_DELETED)->orderBy('sort'),
                                )
                                ->getOptionLabelFromRecordUsing(function (PageType $record): string {
                                    $label = trim((string) ($record->description ?? 'Tipologia'));
                                    $max = max(1, (int) ($record->max_pages ?? 1));

                                    return $label . ' (max ' . $max . ')';
                                })
                                ->helperText('Puoi ripetere la stessa Tipologia con Posizioni diverse, entro il Numero massimo.'),
                        ]),
                ]),

            AuditSection::make(
                statusLabel: 'Stato',
                statusLabels: TemplateType::statusLabels(),
            ),
        ]);
    }
}
