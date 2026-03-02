<?php

namespace App\Filament\Resources\Invitations\Schemas;

use App\Filament\Support\AuditSection;
use App\Models\User;
use App\Models\Invitation;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class InvitationForm
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
                        ->columnSpan(2)
                        ->schema([
                            TextInput::make('email')
                                ->label('Email destinatario Invito')
                                ->email()
                                ->required()
                                ->maxLength(255)
                                ->columnSpan(1),

                            Select::make('status')
                                ->label('Stato')
                                ->options(Invitation::statusLabels())
                                ->default(Invitation::STATUS_READY)
                                ->required()
                                ->columnSpan(1),

                            Select::make('role')
                                ->label('Ruolo')
                                ->options(self::roleOptions())
                                ->required()
                                ->default(User::ROLE_EXTERNAL_COLLABORATORE),

                            Select::make('school_id')
                                ->label('Scuola')
                                ->relationship(
                                    name: 'school',
                                    titleAttribute: 'description',
                                    modifyQueryUsing: function (Builder $query): Builder {
                                        /** @var User|null $user */
                                        $user = Auth::user();

                                        if (($user?->role ?? null) === User::ROLE_EXTERNAL_REFERENTE) {
                                            $schoolIds = $user->schools()->pluck('schools.id');

                                            $query->whereIn('id', $schoolIds);
                                        }

                                        return $query->orderBy('sort');
                                    },
                                )
                                ->searchable()
                                ->preload()
                                ->visible(function (callable $get): bool {
                                    $role = (string) ($get('role') ?? '');

                                    return str_starts_with($role, 'external_');
                                })
                                ->required(function (callable $get): bool {
                                    $role = (string) ($get('role') ?? '');

                                    return str_starts_with($role, 'external_');
                                })
                                ->default(function (): ?int {
                                    /** @var User|null $user */
                                    $user = Auth::user();

                                    if (($user?->role ?? null) !== User::ROLE_EXTERNAL_REFERENTE) {
                                        return null;
                                    }

                                    $ids = $user->schools()->pluck('schools.id');

                                    return $ids->count() === 1 ? (int) $ids->first() : null;
                                })
                                ->columnSpan(1),
                        ]),

                    Section::make('Messaggio')
                        ->columns(2)
                        ->columnSpan(2)
                        ->schema([
                            TextInput::make('subject')
                                ->label('Oggetto')
                                ->maxLength(255)
                                ->placeholder('Invito ad accedere al CMS Diari – attivazione account')
                                ->columnSpanFull(),

                            Textarea::make('message')
                                ->label('Messaggio')
                                ->rows(7)
                                ->placeholder(function (): string {
                                    /** @var User|null $user */
                                    $user = Auth::user();

                                    $firma = $user?->first_name ?: 'Il team CMS Diari';

                                    return
                                        "Ciao!\n\n" .
                                        "Sei stato invitato/a ad accedere al CMS Diari per collaborare alla compilazione dei contenuti del Diario scolastico.\n" .
                                        "Riceverai un link personale per creare il tuo Account e impostare la password.\n\n" .
                                        "Se non ti aspettavi questo invito, puoi ignorare questa email.\n\n" .
                                        "Grazie,\n" .
                                        $firma;
                                })
                                ->columnSpanFull(),
                        ]),
                ]),

            AuditSection::make(
                statusLabel: 'Stato',
                statusLabels: Invitation::statusLabels(),
            ),
        ]);
    }

    private static function roleOptions(): array
    {
        /** @var User|null $user */
        $user = Auth::user();

        $role = (string) ($user?->role ?? '');

        return match ($role) {
            'adminadmin' => [
                'adminadmin' => 'Admin gestionale',
                'internalredattore' => 'Redattore',
                'internalgrafico' => 'Grafico',
                'externalreferente' => 'Referente',
                'externalcollaboratore' => 'Collaboratore',
            ],
            'internalredattore' => [
                'internalgrafico' => 'Grafico',
                'externalreferente' => 'Referente',
                'externalcollaboratore' => 'Collaboratore',
            ],
            'internalgrafico' => [
                'internalgrafico' => 'Grafico',
                'externalreferente' => 'Referente',
                'externalcollaboratore' => 'Collaboratore',
            ],
            'externalreferente' => [
                'externalcollaboratore' => 'Collaboratore',
            ],
            default => [],
        };
    }
}
