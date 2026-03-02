<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Filament\Support\AuditSection;
use App\Models\User;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configureSchema(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Section::make('Anagrafica')
                        ->columns(2)
                        ->columnSpan(1)
                        ->schema([
                            TextEntry::make('full_name')
                                ->label('Nome completo')
                                ->state(fn(User $record): string => (string) $record->full_name)
                                ->placeholder('-')
                                ->columnSpan(1),

                            TextEntry::make('email')
                                ->label('Email')
                                ->placeholder('-')
                                ->columnSpan(1),

                            TextEntry::make('role')
                                ->label('Ruolo')
                                ->badge()
                                ->formatStateUsing(fn(?string $state): string => User::roleLabel($state))
                                ->color(fn(?string $state): string => User::roleColor($state))
                                ->placeholder('-')
                                ->columnSpanFull(),
                        ]),

                    Section::make('Organizzazione')
                        ->columns(1)
                        ->columnSpan(1)
                        ->schema([
                            TextEntry::make('company')
                                ->label('Azienda')
                                ->placeholder('-')
                                ->columnSpanFull(),

                            TextEntry::make('schools.description')
                                ->label('Scuole')
                                ->listWithLineBreaks()
                                ->placeholder('-')
                                ->columnSpanFull(),
                        ]),
                ]),

            AuditSection::make(
                statusLabel: 'Stato',
                statusLabels: User::statusLabels(),
            ),
        ]);
    }
}
