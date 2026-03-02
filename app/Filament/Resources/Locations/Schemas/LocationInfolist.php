<?php

namespace App\Filament\Resources\Locations\Schemas;

use App\Filament\Support\AuditSection;
use App\Models\Location;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LocationInfolist
{
    public static function configureSchema(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->schema([
                    Section::make('Scuola')
                        ->columns(1)
                        ->columnSpan(1)
                        ->schema([
                            TextEntry::make('school.description')
                                ->label('Scuola')
                                ->placeholder('-')
                                ->columnSpanFull(),
                        ]),

                    Section::make('Sede')
                        ->columns(1)
                        ->columnSpan(1)
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

            AuditSection::make(
                statusLabel: 'Stato',
                statusLabels: Location::statusLabels(),
            ),
        ]);
    }
}
