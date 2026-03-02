<?php

namespace App\Filament\Resources\Locations\Schemas;

use App\Filament\Support\AuditSection;
use App\Models\Location;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LocationForm
{
    public static function configureSchema(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns(2)
                    ->schema([
                        Section::make('Scuola')
                            ->columns(1)
                            ->columnSpan(1)
                            ->schema([
                                Select::make('school_id')
                                    ->label('Scuola')
                                    ->columnSpanFull()
                                    ->relationship(
                                        name: 'school',
                                        titleAttribute: 'description',
                                        modifyQueryUsing: fn($query) => $query->orderBy('sort'),
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                            ]),

                        Section::make('Sede')
                            ->columns(2)
                            ->columnSpan(1)
                            ->schema([
                                Hidden::make('status')
                                    ->default(Location::STATUS_ACTIVE)
                                    ->required(),

                                TextInput::make('description')
                                    ->label('Nome')
                                    ->maxLength(255)
                                    ->required()
                                    ->columnSpanFull(),

                                TextInput::make('address')
                                    ->label('Indirizzo')
                                    ->maxLength(255)
                                    ->nullable()
                                    ->columnSpanFull(),
                            ]),
                    ]),

                AuditSection::make(
                    statusLabel: 'Stato',
                    statusLabels: Location::statusLabels(),
                ),
            ]);
    }
}
