<?php

namespace App\Filament\Resources\Pages\Schemas;

use App\Filament\Support\AuditSection;
use App\Models\Page;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PageInfolist
{
    public static function configureSchema(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Dettagli Pagina')
                ->columns(3)
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('description')
                        ->label('Nome')
                        ->placeholder('-')
                        ->columnSpan(1),

                    TextEntry::make('pageType.description')
                        ->label('Tipologia Pagina')
                        ->placeholder('-')
                        ->columnSpan(1),

                    TextEntry::make('template.templateType.description')
                        ->label('Modello Diario')
                        ->placeholder('-')
                        ->columnSpan(1),

                    TextEntry::make('school.description')
                        ->label('Scuola')
                        ->placeholder('-')
                        ->columnSpan(2),

                    TextEntry::make('order.description')
                        ->label('Ordine')
                        ->placeholder('-')
                        ->columnSpan(1),

                    TextEntry::make('icon_url')
                        ->label('Icona')
                        ->placeholder('-')
                        ->columnSpan(1),

                    TextEntry::make('position')
                        ->label('Posizione')
                        ->placeholder('-')
                        ->columnSpan(1),

                    TextEntry::make('space')
                        ->label('Spazio in facciate')
                        ->placeholder('-')
                        ->columnSpan(1),
                ]),


            Section::make('Struttura')
                ->columns(1)
                ->columnSpanFull()
                ->schema([
                    ViewEntry::make('structure_render')
                        ->hiddenLabel()
                        ->columnSpanFull()
                        ->view('filament.structures.structure-toggle')
                        ->viewData([
                            'structure' => static fn(?Page $record): mixed => $record?->structure,
                            'constraints' => static fn(?Page $record): ?array => is_array($record?->template?->templateType?->constraints)
                                ? $record->template->templateType->constraints
                                : null,
                            'editableValues' => false,
                            'hideValues' => false,
                        ]),
                ]),

            AuditSection::make(
                statusLabel: 'Stato',
                statusLabels: Page::statusLabels(),
            ),
        ]);
    }

    private static function toPrettyJson(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $value = trim($value);

            if ($value === '') {
                return null;
            }

            $decoded = json_decode($value, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $value = $decoded;
            } else {
                return $value;
            }
        }

        return json_encode(
            $value,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }
}
