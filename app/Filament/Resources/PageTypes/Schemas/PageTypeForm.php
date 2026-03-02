<?php

declare(strict_types=1);

namespace App\Filament\Resources\PageTypes\Schemas;

use App\Filament\Support\AuditSection;

use App\Support\SettingsRepository;
use App\Models\PageType;
use App\Models\TemplateType;
use App\Models\User;
use App\Structures\StructureFieldFormats;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Filament\Schemas\Components\Component;
use Livewire\Component as LivewireComponent;
use Illuminate\Support\Str;

class PageTypeForm
{
    public static function configureSchema(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Dettagli Tipologia Pagina')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Hidden::make('status')
                        ->default(PageType::STATUS_ACTIVE)
                        ->required(),

                    TextInput::make('description')
                        ->label('Nome')
                        ->nullable()
                        ->columnSpan(1),

                    TextInput::make('icon_url')
                        ->label('Icona')
                        ->nullable()
                        ->columnSpan(1),

                    TextInput::make('space')
                        ->label('Spazio in facciate')
                        ->numeric()
                        ->minValue(0.25)
                        ->maxValue(2.00)
                        ->step(0.25)
                        ->default(1.00)
                        ->required()
                        ->columnSpan(1),

                    TextInput::make('max_pages')
                        ->label('Numero massimo occorrenze nel Modello Generico')
                        ->numeric()
                        ->minValue(1)
                        ->default(1)
                        ->required()
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
                            'label' => 'Struttura',
                            'toggleMethod' => 'toggleStructureEditorMode',
                            'mode' => fn($livewire): string => (string) ($livewire->structureEditorMode ?? 'html'),
                            'error' => fn($livewire): ?string => $livewire->structureJsonError ?? null,
                            'canEdit' => fn(): bool => self::canEditStructure(),
                        ]),

                    Repeater::make('structure_fields')
                        ->label('Campi')
                        ->columns(8)
                        ->columnSpanFull()
                        ->dehydrated(false)
                        ->disabled(fn(): bool => ! self::canEditStructure())
                        ->visible(fn($livewire): bool => (string) ($livewire->structureEditorMode ?? 'html') === 'html')
                        ->default([])
                        ->reorderable()
                        ->addActionLabel('Aggiungi Campo')
                        ->schema([
                            TextInput::make('label')
                                ->label('Nome')
                                ->nullable()
                                ->maxLength(255)
                                ->columnSpan(2),

                            Select::make('format')
                                ->label('Formato')
                                ->options(StructureFieldFormats::options())
                                ->native(false)
                                ->required()
                                ->default(StructureFieldFormats::FORMAT_TEXT)
                                ->live()
                                ->columnSpan(2),

                            Select::make('max_characters_key')
                                ->label('Tipologia')
                                ->options(fn(): array => self::maxCharactersKeyOptions())
                                ->native(false)
                                ->searchable()
                                ->nullable()
                                ->visible(fn(LivewireComponent $livewire, Component $component): bool => self::isTextFormat($livewire, $component))
                                ->columnSpan(2),

                            Select::make('max_size')
                                ->label('Max MB')
                                ->options(fn(): array => self::maxUploadMbOptions())
                                ->native(false)
                                ->nullable()
                                ->visible(fn(LivewireComponent $livewire, Component $component): bool => self::isImageFormat($livewire, $component))
                                ->columnSpan(2),

                            TextInput::make('table_size')
                                ->label('Dimensione tabella')
                                ->helperText('Esempio: 9x6 oppure 15x4')
                                ->nullable()
                                ->maxLength(20)
                                ->visible(fn(LivewireComponent $livewire, Component $component): bool => self::isEmptyTableFormat($livewire, $component))
                                ->columnSpan(2),

                            TextInput::make('max_characters')
                                ->label('Max caratteri')
                                ->numeric()
                                ->disabled()
                                ->dehydrated(false)
                                ->helperText('Calcolato in base alla Taglia.')
                                ->nullable()
                                ->visible(fn(LivewireComponent $livewire, Component $component): bool => self::isTextFormat($livewire, $component))
                                ->columnSpan(2),
                        ]),

                    Textarea::make('structure_json')
                        ->label('JSON')
                        ->rows(18)
                        ->dehydrated(false)
                        ->disabled(fn(): bool => ! self::canEditStructure())
                        ->visible(fn($livewire): bool => (string) ($livewire->structureEditorMode ?? 'html') === 'json')
                        ->rule('json'),

                    AuditSection::make(
                        statusLabel: 'Stato',
                        statusLabels: PageType::statusLabels(),
                    ),
                ]),
        ]);
    }

    private static function canEditStructure(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        $role = (string) ($user->role ?? '');

        return str_starts_with($role, 'admin') || str_starts_with($role, 'internal');
    }

    private static function isTextFormat(LivewireComponent $livewire, Component $component): bool
    {
        return self::isFormat($livewire, $component, StructureFieldFormats::FORMAT_TEXT);
    }

    private static function isEmptyTableFormat(LivewireComponent $livewire, Component $component): bool
    {
        return self::isFormat($livewire, $component, StructureFieldFormats::FORMAT_EMPTY_TABLE);
    }

    private static function isImageFormat(LivewireComponent $livewire, Component $component): bool
    {
        return self::isFormat($livewire, $component, StructureFieldFormats::FORMAT_IMAGE);
    }

    private static function isFormat(LivewireComponent $livewire, Component $component, string $expectedFormat): bool
    {
        $format = self::currentRepeaterItemFormat($livewire, $component);

        if (! is_string($format) || trim($format) === '') {
            return mb_strtolower(trim($expectedFormat)) === mb_strtolower(trim(StructureFieldFormats::FORMAT_TEXT));
        }

        return mb_strtolower(trim($format)) === mb_strtolower(trim($expectedFormat));
    }

    private static function currentRepeaterItemFormat(LivewireComponent $livewire, Component $component): ?string
    {
        if (! isset($livewire->form)) {
            return null;
        }

        $statePath = $component->getStatePath();

        if (! is_string($statePath) || trim($statePath) === '') {
            return null;
        }

        $statePath = trim($statePath);
        if (str_starts_with($statePath, 'data.')) {
            $statePath = substr($statePath, 5);
        }

        $formatPath = self::siblingStatePath($statePath, 'format');

        $rawState = $livewire->form->getRawState();
        $format = data_get($rawState, $formatPath);

        return is_string($format) ? trim($format) : null;
    }

    private static function siblingStatePath(string $statePath, string $siblingKey): string
    {
        $statePath = trim($statePath);

        if ($statePath === '') {
            return $siblingKey;
        }

        $pos = strrpos($statePath, '.');

        if ($pos === false) {
            return $siblingKey;
        }

        return substr($statePath, 0, $pos) . '.' . $siblingKey;
    }

    private static function maxCharactersKeyOptions(): array
    {
        static $cached = null;

        if (is_array($cached)) {
            return $cached;
        }

        $keys = [];

        $allConstraints = TemplateType::query()
            ->whereNotNull('constraints')
            ->pluck('constraints')
            ->all();

        foreach ($allConstraints as $constraints) {
            if (! is_array($constraints)) {
                continue;
            }

            foreach (array_keys($constraints) as $key) {
                $key = trim((string) $key);

                if ($key === '') {
                    continue;
                }

                $keys[$key] = $key;
            }
        }

        ksort($keys);

        $options = [];

        foreach ($keys as $key) {
            $options[$key] = self::maxCharactersKeyLabel($key);
        }

        $cached = $options;

        return $cached;
    }

    private static function maxCharactersKeyLabel(string $key): string
    {
        $key = trim($key);
        $keyLower = mb_strtolower($key);

        $known = match ($keyLower) {
            'short' => 'Breve',
            'medium' => 'Medio',
            'long' => 'Lungo',
            'title' => 'Titolo',
            'subtitle' => 'Sottotitolo',
            'caption' => 'Didascalia',
            'paragraph' => 'Paragrafo',
            'body' => 'Corpo testo',
            'note' => 'Nota',
            'small' => 'Piccolo',
            'large' => 'Grande',
            'xsmall', 'x-small', 'xs' => 'XS',
            'smallsize', 'small-size', 's' => 'S',
            'mediumsize', 'medium-size', 'm' => 'M',
            'largesize', 'large-size', 'l' => 'L',
            'xlarge', 'x-large', 'xl' => 'XL',
            'xxlarge', 'xx-large', 'xxl' => 'XXL',
            default => null,
        };

        if (is_string($known) && $known !== '') {
            return $known;
        }

        return (string) Str::of($keyLower)
            ->replace(['_', '-'], ' ')
            ->squish()
            ->ucfirst();
    }

    private static function maxUploadMbOptions(): array
    {
        $max = SettingsRepository::getInt('MAX_UPLOAD_MB', 20);

        if ($max <= 0) {
            $max = 20;
        }

        $options = [];

        for ($i = 1; $i <= $max; $i++) {
            $options[$i] = (string) $i;
        }

        return $options;
    }
}
