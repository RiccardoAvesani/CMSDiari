<?php

namespace App\Filament\Resources\Campaigns\Schemas;

use App\Filament\Support\AuditSection;
use App\Models\Campaign;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CampaignForm
{
    public static function configureSchema(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Campagna')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    Hidden::make('status')
                        ->default(Campaign::STATUS_PLANNED)
                        ->required(),

                    TextInput::make('year')
                        ->label('Anno')
                        ->required()
                        ->maxLength(4)
                        ->rule('regex:/^\d{4}$/')
                        ->placeholder('20XX'),

                    TextInput::make('description')
                        ->label('Nome')
                        ->placeholder('Campagna Diari 20XX'),
                ]),

            AuditSection::make(
                statusLabel: 'Stato',
                statusLabels: Campaign::statusLabels(),
            ),
        ]);
    }
}
