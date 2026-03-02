<?php

declare(strict_types=1);

namespace App\Filament\Resources\Templates\Schemas;

use App\Filament\Support\AuditSection;
use App\Models\Template;
use App\Models\TemplateType;
use App\Models\User;
use App\Support\SettingsRepository;
use App\Structures\StructureFormFactory;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;

class TemplateForm
{
    public static function configureSchema(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Dettagli Modello')
                ->columns(5)
                ->columnSpanFull()
                ->schema([
                    Hidden::make('status')
                        ->default(Template::STATUS_ACTIVE)
                        ->required(),

                    TextInput::make('description')
                        ->label('Nome')
                        ->maxLength(255)
                        ->nullable()
                        ->columnSpan(1),

                    Select::make('school_id')
                        ->label('Scuola')
                        ->relationship(
                            name: 'school',
                            titleAttribute: 'description',
                            modifyQueryUsing: fn(Builder $query) => $query->orderBy('sort'),
                        )
                        ->searchable()
                        ->preload()
                        ->disabled()
                        ->dehydrated(false)
                        ->columnSpan(2),

                    Select::make('template_type_id')
                        ->label('Modello Generico')
                        ->relationship(
                            name: 'templateType',
                            titleAttribute: 'description',
                            modifyQueryUsing: fn(Builder $query) => $query
                                ->where('status', TemplateType::STATUS_ACTIVE)
                                ->orderBy('sort'),
                        )
                        ->searchable()
                        ->preload()
                        ->disabled()
                        ->dehydrated(false)
                        ->columnSpan(1),

                    Select::make('order_id')
                        ->label('Ordine')
                        ->relationship(
                            name: 'order',
                            titleAttribute: 'external_id',
                            modifyQueryUsing: fn(Builder $query) => $query
                                ->orderBy('sort')
                                ->orderByDesc('id'),
                        )
                        ->searchable()
                        ->preload()
                        ->disabled()
                        ->dehydrated(false)
                        ->columnSpan(1),

                    Toggle::make('is_custom_finale')
                        ->label('Sezione finale')
                        ->disabled()
                        ->dehydrated()
                        ->visible(fn(?Template $record): bool => (bool) ($record?->is_custom_finale ?? false))
                        ->columnSpanFull(),

                    Toggle::make('is_giustificazioni')
                        ->label('Giustificazioni di assenza tot. 32')
                        ->default(false)
                        ->visible(fn(?Template $record): bool => (bool) ($record?->is_custom_finale ?? false))
                        ->columnSpan(1),

                    Toggle::make('is_permessi')
                        ->label('Permessi entrata/uscita tot. 16')
                        ->default(false)
                        ->visible(fn(?Template $record): bool => (bool) ($record?->is_custom_finale ?? false))
                        ->columnSpan(1),

                    Toggle::make('is_visite')
                        ->label('Benestare alle Visite guidate, Informativa Sicurezza e Ricevuta')
                        ->default(false)
                        ->visible(fn(?Template $record): bool => (bool) ($record?->is_custom_finale ?? false))
                        ->columnSpan(3),
                ]),

            AuditSection::make(
                statusLabel: 'Stato',
                statusLabels: Template::statusLabels(),
            ),
        ]);
    }
}
