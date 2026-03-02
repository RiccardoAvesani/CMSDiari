<?php

namespace App\Filament\Resources\Campaigns\Schemas;

use App\Filament\Support\AuditSection;
use App\Models\Campaign;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CampaignInfolist
{
    public static function configureSchema(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Campagna')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextEntry::make('year')
                        ->label('Anno')
                        ->placeholder('-'),

                    TextEntry::make('description')
                        ->label('Nome')
                        ->placeholder('-')
                ]),

            AuditSection::make(
                statusLabel: 'Stato',
                statusLabels: Campaign::statusLabels(),
            ),
        ]);
    }
}
