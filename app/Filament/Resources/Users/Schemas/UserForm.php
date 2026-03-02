<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Filament\Support\AuditSection;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Password as PasswordRule;

class UserForm
{
    public static function configureSchema(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Section::make('Avatar')
                        ->columns(1)
                        ->columnSpan(1)
                        ->schema([
                            FileUpload::make('avatar_url')
                                ->label('Immagine profilo')
                                ->avatar()
                                ->image()
                                ->imageEditor()
                                ->disk('public')
                                ->directory('avatars')
                                ->visibility('public')
                                ->nullable()
                        ]),

                    Section::make('Informazioni personali')
                        ->columns(2)
                        ->columnSpan(1)
                        ->schema([
                            TextInput::make('first_name')
                                ->label('Nome')
                                ->required()
                                ->maxLength(255),

                            TextInput::make('last_name')
                                ->label('Cognome')
                                ->required()
                                ->maxLength(255),

                            DatePicker::make('born_at')
                                ->label('Data di nascita')
                                ->native(false)
                                ->nullable(),

                            TextInput::make('company')
                                ->label('Azienda')
                                ->maxLength(255)
                                ->nullable(),
                        ]),

                    Section::make('Credenziali')
                        ->columns(2)
                        ->columnSpanFull()
                        ->schema([
                            TextInput::make('email')
                                ->label('Email')
                                ->email()
                                ->required()
                                ->maxLength(255)
                                ->unique(ignoreRecord: true),

                            TextInput::make('password')
                                ->label('Password')
                                ->password()
                                ->required(fn(string $context): bool => $context === 'create')
                                ->dehydrated(fn($state): bool => filled($state))
                                ->rule(PasswordRule::defaults()),

                            Toggle::make('force_renew_password')
                                ->label('Richiedi cambio password')
                                ->default(false),
                        ]),

                    Section::make('Impostazioni')
                        ->columns(2)
                        ->columnSpanFull()
                        ->schema([
                            Select::make('status')
                                ->label('Stato')
                                ->options(User::statusLabels())
                                ->required()
                                ->columnSpan(1)
                                ->default(User::STATUS_ACTIVE),

                            Select::make('role')
                                ->label('Ruolo')
                                ->options(User::roleOptions())
                                ->required()
                                ->default(User::ROLE_EXTERNAL_COLLABORATORE)
                                ->live(),

                            Select::make('schools')
                                ->label('Scuole')
                                ->multiple()
                                ->columnSpan(2)
                                ->relationship(
                                    name: 'schools',
                                    titleAttribute: 'description',
                                )
                                ->searchable()
                                ->preload()
                                ->helperText('Seleziona una o più Scuole collegate a questo utente.')
                                ->required(fn($get): bool => User::isExternalRole((string) ($get('role') ?? '')))
                                ->rule(fn($get): string => User::isExternalRole((string) ($get('role') ?? '')) ? 'min:1' : 'nullable'),
                        ]),
                ]),

            AuditSection::make(
                statusLabel: 'Stato',
                statusLabels: User::statusLabels(),
            ),
        ]);
    }
}
