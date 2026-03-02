<?php

declare(strict_types=1);

namespace App\Filament\Resources\Pages\Schemas;

use App\Filament\Support\AuditSection;
use App\Support\SettingsRepository;
use App\Filament\Resources\Pages\PageResource;
use App\Models\Page;
use App\Models\User;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use App\Structures\StructureFormFactory;
use Illuminate\Support\Facades\Auth;

class PageForm
{
    public static function configureSchema(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Dettagli Pagina')
                ->columns(3)
                ->columnSpanFull()
                ->schema([
                    Hidden::make('status')
                        ->default(Page::STATUS_ACTIVE)
                        ->required(),

                    TextInput::make('description')
                        ->label('Nome')
                        ->required()
                        ->disabled(fn(): bool => self::isExternal())
                        ->dehydrated(fn(): bool => ! self::isExternal())
                        ->columnSpan(1),

                    Select::make('page_type_id')
                        ->label('Tipologia Pagina')
                        ->relationship(
                            name: 'pageType',
                            titleAttribute: 'description',
                            modifyQueryUsing: fn($query) => $query->orderBy('sort'),
                        )
                        ->searchable()
                        ->preload()
                        ->required()
                        ->disabled()
                        ->dehydrated(false)
                        ->columnSpan(1),

                    Select::make('template_id')
                        ->label('Modello Compilato')
                        ->relationship(
                            name: 'template',
                            titleAttribute: 'description',
                            modifyQueryUsing: fn($query) => $query->orderBy('sort'),
                        )
                        ->searchable()
                        ->preload()
                        ->required()
                        ->disabled()
                        ->dehydrated(false)
                        ->columnSpan(1),

                    Select::make('school_id')
                        ->label('Scuola')
                        ->relationship(
                            name: 'school',
                            titleAttribute: 'description',
                            modifyQueryUsing: fn($query) => $query->orderBy('sort'),
                        )
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->disabled()
                        ->dehydrated(false)
                        ->columnSpan(2),

                    Select::make('order_id')
                        ->label('Ordine')
                        ->relationship(
                            name: 'order',
                            titleAttribute: 'external_id',
                            modifyQueryUsing: fn($query) => $query->orderBy('sort'),
                        )
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->disabled()
                        ->dehydrated(false)
                        ->columnSpan(1),

                    TextInput::make('icon_url')
                        ->label('Icona')
                        ->nullable()
                        ->disabled()
                        ->dehydrated(false)
                        ->columnSpan(1),

                    TextInput::make('position')
                        ->label('Posizione')
                        ->numeric()
                        ->disabled()
                        ->dehydrated(false)
                        ->columnSpan(1),

                    TextInput::make('space')
                        ->label('Spazio in facciate')
                        ->numeric()
                        ->nullable()
                        ->disabled()
                        ->dehydrated(false)
                        ->columnSpan(1),
                ]),

            Section::make('Struttura')
                ->columns(1)
                ->columnSpanFull()
                ->schema([
                    ViewField::make('structure_toolbar')
                        ->hiddenLabel()
                        ->view('filament.structures.structure-editor-toolbar')
                        ->dehydrated(false)
                        ->viewData([
                            'label' => 'Compilazione',
                            'toggleMethod' => self::isExternal() ? null : 'toggleStructureEditorMode',
                            'mode' => fn($livewire): string => (string) ($livewire->structureEditorMode ?? 'html'),
                            'error' => fn($livewire): ?string => $livewire->structureJsonError ?? null,
                            'canEdit' => fn($livewire): bool => method_exists($livewire, 'toggleStructureEditorMode'),
                            'canEditCompiledValues' => fn($livewire): bool => method_exists($livewire, 'canEditCompiledValues')
                                ? (bool) $livewire->canEditCompiledValues()
                                : false,
                        ]),

                    Section::make()
                        ->label('HTML')
                        ->visible(fn($livewire): bool => (string) ($livewire->structureEditorMode ?? 'html') === 'html')
                        ->schema(function (?Page $record): array {
                            $structure = $record?->structure ?? null;
                            $structure = self::normalizeStructureForValueForm($structure);

                            $constraints = $record?->template?->templateType?->constraints ?? null;

                            $settings = [
                                'MAX_UPLOAD_MB' => SettingsRepository::getInt('MAX_UPLOAD_MB', 20),
                                'IMAGE_MIN_DPI' => SettingsRepository::getInt('IMAGE_MIN_DPI', 300),
                                'IMAGE_ALLOWED_FORMATS' => SettingsRepository::getArray('IMAGE_ALLOWED_FORMATS', ['jpg', 'jpeg', 'png', 'tif', 'tiff']),
                                'AUTO_SAVE_SECONDS' => SettingsRepository::getInt('AUTO_SAVE_SECONDS', 15),
                            ];

                            /** @var User|null $user */
                            $user = Auth::user();

                            $isExternal = str_starts_with((string) ($user?->role ?? ''), 'external');

                            $valuesEditable = $isExternal
                                ? PageResource::canExternalEditValues($record?->order)
                                : true;

                            return StructureFormFactory::makeValueComponents(
                                structure: $structure,
                                statePrefix: 'structure_values',
                                constraints: is_array($constraints) ? $constraints : null,
                                settings: $settings,
                                valuesEditable: $valuesEditable,
                            );
                        }),

                    Textarea::make('structure_json')
                        ->label('JSON')
                        ->rows(18)
                        ->dehydrated(false)
                        ->visible(function ($livewire): bool {
                            if (self::isExternal()) {
                                return false;
                            }

                            return (string) ($livewire->structureEditorMode ?? 'html') === 'json';
                        })
                        ->disabled(fn($livewire): bool => ! (method_exists($livewire, 'canEditCompiledValues') && (bool) $livewire->canEditCompiledValues()))
                        ->rule('json'),
                ]),

            AuditSection::make(
                statusLabel: 'Stato',
                statusLabels: Page::statusLabels(),
            ),
        ]);
    }

    private static function isExternal(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        return str_starts_with((string) ($user->role ?? ''), 'external');
    }

    /**
     * Rende la struttura sempre una lista di blocchi (int keys), perché
     * StructureFormFactory si aspetta un array "list" e altrimenti non renderizza nulla.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function normalizeStructureForValueForm(mixed $structure): array
    {
        if (! is_array($structure)) {
            return [];
        }

        if (array_is_list($structure)) {
            return $structure;
        }

        if (array_key_exists('fields', $structure) && is_array($structure['fields'] ?? null)) {
            return [
                ['Struttura' => $structure],
            ];
        }

        $keys = array_keys($structure);

        if (count($keys) === 1 && is_string($keys[0]) && trim($keys[0]) !== '') {
            return [$structure];
        }

        $out = [];

        foreach ($structure as $k => $v) {
            if (! is_string($k) || trim($k) === '') {
                continue;
            }

            if (! is_array($v)) {
                continue;
            }

            $out[] = [$k => $v];
        }

        return $out;
    }
}
