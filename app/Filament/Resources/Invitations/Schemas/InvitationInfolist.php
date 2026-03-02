<?php

namespace App\Filament\Resources\Invitations\Schemas;

use App\Filament\Support\AuditSection;
use App\Models\Invitation;
use App\Models\User;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InvitationInfolist
{
    public static function configureSchema(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Section::make('Destinatario')
                        ->columns(2)
                        ->schema([
                            TextEntry::make('email')
                                ->label('Email destinatario Invito')
                                ->placeholder('-')
                                ->columnSpan(2),

                            TextEntry::make('role')
                                ->label('Ruolo')
                                ->badge()
                                ->formatStateUsing(fn(?string $state): string => User::roleLabel($state))
                                ->color(fn(?string $state): string => User::roleColor($state))
                                ->placeholder('-'),

                            TextEntry::make('school.description')
                                ->label('Scuola')
                                ->placeholder('-'),
                        ]),

                    Section::make('Date')
                        ->columns(2)
                        ->columnSpan(1)
                        ->schema([
                            TextEntry::make('sent_at')
                                ->label('Inviato il')
                                ->dateTime('d/m/Y H:i')
                                ->placeholder('-'),

                            TextEntry::make('expires_at')
                                ->label('Scade il')
                                ->dateTime('d/m/Y H:i')
                                ->placeholder('-'),

                            TextEntry::make('received_at')
                                ->label('Invito aperto il')
                                ->dateTime('d/m/Y H:i')
                                ->placeholder('-'),

                            TextEntry::make('registered_at')
                                ->label('Utente registrato il')
                                ->dateTime('d/m/Y H:i')
                                ->placeholder('-'),
                        ]),
                ]),

            Section::make('Contenuto')
                ->columns(1)
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('subject')
                        ->label('Oggetto')
                        ->placeholder('-'),

                    TextEntry::make('message')
                        ->label('Messaggio')
                        ->placeholder('-'),
                ]),

            Section::make('Utente Registrato')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('user.full_name')
                        ->label('Nome')
                        ->state(fn(Invitation $record): string => User::formatUserName($record->user))
                        ->placeholder('-')
                        ->columnSpan(1),

                    TextEntry::make('user.email')
                        ->label('Email')
                        ->placeholder('-')
                        ->columnSpan(1),

                    TextEntry::make('access_token')
                        ->label('Access token')
                        ->placeholder('-')
                        ->columnSpan(1),

                    TextEntry::make('open_token')
                        ->label('Open token')
                        ->placeholder('-')
                        ->columnSpan(1),
                ]),

            AuditSection::make(
                statusLabel: 'Stato',
                statusLabels: Invitation::statusLabels(),
            ),
        ]);
    }
}
