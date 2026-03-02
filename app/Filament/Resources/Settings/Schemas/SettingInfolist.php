<?php

namespace App\Filament\Resources\Settings\Schemas;

use App\Filament\Resources\Settings\SettingResource;
use App\Filament\Support\AuditSection;
use App\Models\Setting;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SettingInfolist
{
    public static function configureSchema(Schema $schema): Schema
    {
        $environmentLabels = Setting::environmentOptions();
        $permissionLabels = Setting::permissionOptions();

        return $schema->components([
            Section::make('Dettaglio')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Section::make('Impostazione')
                        ->columns(2)
                        ->columnSpan(1)
                        ->schema([
                            TextEntry::make('description')
                                ->label('Nome')
                                ->placeholder('-')
                                ->columnSpanFull(),

                            TextEntry::make('instructions')
                                ->label('Descrizione')
                                ->placeholder('-')
                                ->columnSpanFull(),
                    ]),

                    Section::make('Proprietà')
                        ->columns(2)
                        ->columnSpan(1)
                        ->schema([
                            TextEntry::make('value')
                                ->label('Valore')
                                ->formatStateUsing(function ($state, Setting $record): string {
                                    return SettingResource::formatValueForTable((string) ($record->description ?? ''), $state);
                                })
                                ->columnSpanFull(),

                            TextEntry::make('environment')
                                ->label('Ambiente')
                                ->formatStateUsing(fn(?string $state): string => $state ? ($environmentLabels[$state] ?? 'Sconosciuto') : '-')
                                ->placeholder('-'),

                            TextEntry::make('permission')
                                ->label('Permessi')
                                ->formatStateUsing(fn($state): string => $state !== null ? ($permissionLabels[(string) $state] ?? 'Sconosciuto') : '-')
                                ->placeholder('-'),

                            TextEntry::make('is_active')
                                ->label('Abilitata')
                                ->formatStateUsing(fn($state): string => (bool) $state ? 'Sì' : 'No'),
                    ]),
                ]),

            AuditSection::make(
                statusLabel: 'Stato',
                statusLabels: Setting::statusLabels(),
            ),
        ]);
    }
}
