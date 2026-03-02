<?php

namespace App\Filament\Resources\Contacts\Schemas;

use App\Filament\Support\AuditSection;
use App\Models\Contact;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContactInfolist
{
    public static function configureSchema(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Section::make('Sede')
                        ->columns(2)
                        ->columnSpanFull()
                        ->schema([
                            TextEntry::make('location.school.description')
                                ->label('Scuola')
                                ->placeholder('-')
                                ->columnSpan(1),

                            TextEntry::make('location.description')
                                ->label('Nome')
                                ->placeholder('-')
                                ->columnSpan(1),
                        ]),

                    Section::make('Contatto')
                        ->columns(2)
                        ->columnSpanFull()
                        ->schema([
                            TextEntry::make('full_name')
                                ->label('Nome')
                                ->placeholder('-')
                                ->columnSpan(1),

                            TextEntry::make('telephone')
                                ->label('Telefono')
                                ->placeholder('-')
                                ->columnSpan(1),

                            TextEntry::make('email')
                                ->label('Email')
                                ->placeholder('-')
                                ->columnSpan(2),
                        ]),

                    AuditSection::make(
                        statusLabel: 'Stato',
                        statusLabels: Contact::statusLabels(),
                    ),
                ]),
        ]);
    }
}
