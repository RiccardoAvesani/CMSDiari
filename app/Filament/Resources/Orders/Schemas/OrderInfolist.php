<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Filament\Support\AuditSection;
use App\Models\Order;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderInfoList
{
    public static function configureSchema(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Section::make('Riferimenti')
                        ->columns(2)
                        ->columnSpan(1)
                        ->schema([
                            TextEntry::make('external_id')
                                ->label('ID ETB')
                                ->placeholder('-')
                                ->columnSpan(1),

                            TextEntry::make('campaign.description')
                                ->label('Campagna')
                                ->placeholder('-')
                                ->columnSpan(1),

                            TextEntry::make('school.description')
                                ->label('Scuola')
                                ->placeholder('-')
                                ->columnSpanFull(),
                        ]),

                    Section::make('Produzione')
                        ->columns(2)
                        ->columnSpan(1)
                        ->schema([
                            TextEntry::make('campaign.year')
                                ->label('Anno')
                                ->placeholder('-')
                                ->columnSpan(1),

                            TextEntry::make('quantity')
                                ->label('Quantità')
                                ->numeric()
                                ->placeholder('-')
                                ->columnSpan(1),

                            TextEntry::make('deadline_collection')
                                ->label('Scadenza raccolta')
                                ->dateTime('d/m/Y H:i')
                                ->placeholder('-')
                                ->columnSpan(1),

                            TextEntry::make('deadline_annotation')
                                ->label('Scadenza correzioni')
                                ->dateTime('d/m/Y H:i')
                                ->placeholder('-')
                                ->columnSpan(1),
                        ]),
                ]),

            Section::make('Raccolta dati')
                ->columns(3)
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('template.templateType.description')
                        ->label('Modello Diario')
                        ->placeholder('-')
                        ->columnSpan(1),

                    TextEntry::make('template_pages_progress')
                        ->label('Pagine generate / previste')
                        ->state(function (Order $record): string {
                            $template = $record->template;

                            if (! $template) {
                                return '-';
                            }

                            $generated = $template->pages->count();
                            $expected = $template->templateType?->items?->count();

                            if ($expected === null) {
                                return (string) $generated;
                            }

                            return $generated . ' / ' . $expected;
                        })
                        ->columnSpan(1),

                    TextEntry::make('template.updated_at')
                        ->label('Ultimo aggiornamento Modello')
                        ->dateTime('d/m/Y H:i')
                        ->placeholder('-')
                        ->columnSpan(1),
                ]),

            AuditSection::make(
                statusLabel: 'Stato',
                statusLabels: Order::statusLabels(),
            ),
        ]);
    }
}
