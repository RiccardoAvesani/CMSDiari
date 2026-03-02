<?php

namespace App\Filament\Resources\PageTypes\Schemas;

use App\Filament\Support\AuditSection;
use App\Models\PageType;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PageTypeInfolist
{
    public static function configureSchema(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Dettagli Tipologia Pagina')
                ->columns(4)
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('description')
                        ->label('Nome')
                        ->placeholder('-')
                        ->columnSpan(1),

                    TextEntry::make('icon_url')
                        ->label('Icona')
                        ->placeholder('-')
                        ->columnSpan(1),

                    TextEntry::make('space')
                        ->label('Spazio in facciate')
                        ->placeholder('-')
                        ->columnSpan(1),

                    TextEntry::make('max_pages')
                        ->label('Max occorrenze nel Modello')
                        ->numeric()
                        ->placeholder('-')
                        ->columnSpan(1),
                ]),

            Section::make("Struttura")
                ->columns(1)
                ->columnSpanFull()
                ->schema([
                    ViewEntry::make('structure_render')
                        ->hiddenLabel()
                        ->view('filament.structures.structure-toggle')
                        ->viewData([
                            'structure' => fn(PageType $record): mixed => $record->structure,
                            'constraints' => null,
                            'editableValues' => false,
                            'hideValues' => true,
                        ])
                        ->columnSpanFull(),
                ]),

            AuditSection::make(
                statusLabel: 'Stato',
                statusLabels: PageType::statusLabels(),
            ),
        ]);
    }
}
