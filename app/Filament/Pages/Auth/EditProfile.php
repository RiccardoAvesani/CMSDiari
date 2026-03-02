<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Password as PasswordRule;

class EditProfile extends BaseEditProfile
{
    public function form(Schema $schema): Schema
    {
        $schema = parent::form($schema);

        return $schema->components([
            Section::make('Avatar')
                ->schema([
                    FileUpload::make('avatar_url')
                        ->label('Immagine profilo')
                        ->avatar()
                        ->image()
                        ->imageEditor()
                        ->disk('public')
                        ->directory('avatars')
                        ->visibility('public')
                        ->maxSize(2048)
                        ->nullable()
                        ->columnSpanFull()
                        ->alignCenter(),
                ])
                ->columns(1),

            Section::make('Informazioni personali')
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
                ])
                ->columns(2),

            Section::make('Credenziali')
                ->schema([
                    TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),

                    TextInput::make('password')
                        ->label('Nuova password')
                        ->password()
                        ->nullable()
                        ->dehydrated(fn($state): bool => filled($state))
                        ->rule(PasswordRule::defaults()),

                    TextInput::make('password_confirmation')
                        ->label('Conferma nuova password')
                        ->password()
                        ->nullable()
                        ->dehydrated(fn($state): bool => filled($state))
                        ->same('password'),
                ])
                ->columns(1),
        ]);
    }
}
