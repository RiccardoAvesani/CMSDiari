<?php

namespace App\Filament\Resources\Schools\Schemas;

use App\Filament\Support\AuditSection;
use App\Models\Contact;
use App\Models\School;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SchoolInfolist
{
    public static function configureSchema(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Section::make('Anagrafica')
                        ->columns(1)
                        ->columnSpan(1)
                        ->schema([
                            TextEntry::make('description')
                                ->label('Scuola')
                                ->placeholder('-')
                                ->columnSpanFull(),
                        ]),

                    Section::make('Dettagli')
                        ->columns(2)
                        ->columnSpan(1)
                        ->schema([
                            TextEntry::make('codice_fiscale')
                                ->label('Codice fiscale')
                                ->placeholder('-'),

                            TextEntry::make('external_id')
                                ->label('ID ETB')
                                ->placeholder('-'),
                        ]),
                ]),

            Section::make('Sedi e Contatti')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Section::make('Sedi')
                        ->columnSpan(1)
                        ->schema([
                            RepeatableEntry::make('locations')
                                ->hiddenLabel(true)
                                ->contained(true)
                                ->columns(1)
                                ->schema([
                                    TextEntry::make('description')
                                        ->label('Sede')
                                        ->placeholder('-')
                                        ->columnSpanFull(),

                                    TextEntry::make('address')
                                        ->label('Indirizzo')
                                        ->placeholder('-')
                                        ->columnSpanFull(),
                                ]),
                        ]),

                    Section::make('Contatti')
                        ->columnSpan(1)
                        ->schema([
                            RepeatableEntry::make('contacts')
                                ->hiddenLabel(true)
                                ->contained(true)
                                ->columns(2)
                                ->schema([
                                    TextEntry::make('location.description')
                                        ->label('Sede')
                                        ->placeholder('-')
                                        ->weight('bold')
                                        ->columnSpan(1),

                                    TextEntry::make('full_name')
                                        ->label('Nome')
                                        ->state(fn(Contact $record): string => trim(($record->first_name ?? '') . ' ' . ($record->last_name ?? '')))
                                        ->placeholder('-')
                                        ->columnSpan(1),

                                    TextEntry::make('telephone')
                                        ->label('Telefono')
                                        ->placeholder('-')
                                        ->columnSpan(1),

                                    TextEntry::make('email')
                                        ->label('Email')
                                        ->placeholder('-')
                                        ->columnSpan(1),
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
