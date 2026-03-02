<?php

namespace App\Filament\Resources\Contacts\Schemas;

use App\Filament\Support\AuditSection;
use App\Models\Contact;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class ContactForm
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
                            Select::make('location_id')
                                ->label('Nome')
                                ->relationship(
                                    name: 'location',
                                    titleAttribute: 'description',
                                    modifyQueryUsing: fn(Builder $query): Builder => $query->orderBy('sort'),
                                )
                                ->searchable()
                                ->preload()
                                ->required()
                                ->columnSpan(1),

                            Select::make('status')
                                ->label('Stato')
                                ->options(Contact::statusLabels())
                                ->default(Contact::STATUS_ACTIVE)
                                ->required()
                                ->columnSpan(1),
                        ]),

                    Section::make('Contatto')
                        ->columns(2)
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
                                ->columnSpan(1),
                        ]),

                    AuditSection::make(
                        statusLabel: 'Stato',
                        statusLabels: Contact::statusLabels(),
                    ),
                ]),
        ]);
    }
}
