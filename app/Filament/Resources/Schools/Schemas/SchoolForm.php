<?php

namespace App\Filament\Resources\Schools\Schemas;

use App\Filament\Support\AuditSection;
use App\Models\Contact;
use App\Models\Location;
use App\Models\School;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class SchoolForm
{
    public static function configureSchema(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Scuola')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('description')
                        ->label('Scuola')
                        ->required()
                        ->columnSpan(1),

                    Select::make('status')
                        ->label('Stato')
                        ->options(School::statusLabels())
                        ->default(School::STATUS_ACTIVE)
                        ->required()
                        ->columnSpan(1),

                    TextInput::make('external_id')
                        ->label('ID ETB')
                        ->unique(ignoreRecord: true)
                        ->columnSpan(1),

                    TextInput::make('codice_fiscale')
                        ->label('Codice fiscale')
                        ->maxLength(16)
                        ->placeholder('AAABBB00C11D222E')
                        ->dehydrateStateUsing(fn(?string $state) => $state ? strtoupper(trim($state)) : null)
                        ->unique(ignoreRecord: true)
                        ->nullable()
                        ->columnSpan(1),
                ]),

            Section::make('Sedi e contatti')
                ->columns(1)
                ->columnSpanFull()
                ->schema([
                    Repeater::make('locations')
                        ->label('Sedi')
                        ->relationship(
                            modifyQueryUsing: fn(Builder $query): Builder => $query->where('status', '!=', Location::STATUS_DELETED),
                        ) // usa la relazione School::locations()
                        ->orderColumn('sort')
                        ->reorderable(true)
                        ->reorderableWithButtons(true)
                        ->defaultItems(0)
                        ->itemLabel(fn(array $state): ?string => $state['description'] ?? null)
                        ->columns(2)
                        ->schema([
                            TextInput::make('description')
                                ->label('Descrizione sede')
                                ->required()
                                ->maxLength(255),

                            Select::make('status')
                                ->label('Stato')
                                ->options(Location::statusLabels())
                                ->default(Location::STATUS_ACTIVE)
                                ->required(),

                            TextInput::make('address')
                                ->label('Indirizzo')
                                ->maxLength(255)
                                ->nullable()
                                ->columnSpanFull(),

                            Repeater::make('contacts')
                                ->label('Contatti')
                                ->relationship(
                                    modifyQueryUsing: fn(Builder $query): Builder => $query->where('status', '!=', Contact::STATUS_DELETED),
                                ) // usa la relazione Location::contacts()
                                ->orderColumn('sort') // richiede colonna `sort` su contacts
                                ->reorderable(true)
                                ->reorderableWithButtons(true)
                                ->defaultItems(0)
                                ->itemLabel(function (array $state): ?string {
                                    $fullName = trim(($state['first_name'] ?? '') . ' ' . ($state['last_name'] ?? ''));
                                    return $fullName !== '' ? $fullName : ($state['email'] ?? null);
                                })
                                ->columns(3)
                                ->columnSpanFull()
                                ->schema([
                                    TextInput::make('first_name')
                                        ->label('Nome')
                                        ->required()
                                        ->maxLength(100)
                                        ->columnSpan(1),

                                    TextInput::make('last_name')
                                        ->label('Cognome')
                                        ->maxLength(100)
                                        ->nullable()
                                        ->columnSpan(1),

                                    Select::make('status')
                                        ->label('Stato')
                                        ->options(Contact::statusLabels())
                                        ->default(Contact::STATUS_ACTIVE)
                                        ->required()
                                        ->columnSpan(1),

                                    TextInput::make('telephone')
                                        ->label('Telefono')
                                        ->tel()
                                        ->maxLength(50)
                                        ->nullable()
                                        ->columnSpan(1),

                                    TextInput::make('email')
                                        ->label('Email')
                                        ->email()
                                        ->maxLength(255)
                                        ->nullable()
                                        ->columnSpan(2),
                                ]),
                        ]),
                ]),

            AuditSection::make(
                statusLabel: 'Stato',
                statusLabels: School::statusLabels(),
            ),
        ]);
    }
}
