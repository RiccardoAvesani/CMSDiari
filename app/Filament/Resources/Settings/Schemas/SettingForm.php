<?php

namespace App\Filament\Resources\Settings\Schemas;

use App\Filament\Resources\Settings\SettingResource;
use App\Models\Setting;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use App\Filament\Support\AuditSection;

class SettingForm
{
    public static function configureSchema(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Section::make('Impostazione')
                        ->columns(2)
                        ->columnSpan(1)
                        ->schema([
                            TextInput::make('description')
                                ->label('Nome')
                                ->required()
                                ->disabled()
                                ->dehydrated(true)
                                ->columnSpanFull(),

                            Textarea::make('instructions')
                                ->label('Descrizione')
                                ->rows(5)
                                ->nullable()
                                ->columnSpanFull(),
                        ]),

                    Section::make('Proprietà')
                        ->columns(2)
                        ->columnSpan(1)
                        ->schema([
                            TextInput::make('value')
                                ->label('Valore (numero)')
                                ->numeric()
                                ->required(fn($get): bool => SettingResource::getValueType((string) ($get('description') ?? '')) === 'int')
                                ->visible(fn($get): bool => SettingResource::getValueType((string) ($get('description') ?? '')) === 'int')
                                ->columnSpanFull(),

                            TagsInput::make('value')
                                ->label('Valore (lista)')
                                ->placeholder('Aggiungi un elemento e premi invio')
                                ->reorderable()
                                ->required(fn($get): bool => SettingResource::getValueType((string) ($get('description') ?? '')) === 'array')
                                ->visible(fn($get): bool => SettingResource::getValueType((string) ($get('description') ?? '')) === 'array')
                                ->columnSpanFull(),

                            Select::make('environment')
                                ->label('Ambiente')
                                ->options(Setting::environmentOptions())
                                ->required()
                                ->default(Setting::ENV_PRODUCTION),

                            Select::make('permission')
                                ->label('Permessi')
                                ->options(Setting::permissionOptions())
                                ->required()
                                ->default(Setting::PERMISSION_1),

                            Select::make('status')
                                ->label('Stato')
                                ->options(Setting::statusLabels())
                                ->required()
                                ->default(Setting::STATUS_ACTIVE),

                            Toggle::make('is_active')
                                ->label('Abilitata')
                                ->default(true)
                                ->required(),
                        ]),
                ]),

            AuditSection::make(
                statusLabel: 'Stato',
                statusLabels: Setting::statusLabels(),
            ),
        ]);
    }
}
