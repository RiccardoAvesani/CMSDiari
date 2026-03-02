<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Filament\Support\AuditSection;
use App\Models\Order;
use App\Models\TemplateType;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class OrderForm
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
                            TextInput::make('external_id')
                                ->label('ID ETB')
                                ->maxLength(64)
                                ->placeholder('ETB-2027-012345')
                                ->unique(ignoreRecord: true)
                                ->columnSpan(1)
                                ->nullable(),

                            Select::make('campaign_id')
                                ->label('Campagna')
                                ->relationship(
                                    name: 'campaign',
                                    titleAttribute: 'description',
                                    modifyQueryUsing: fn($query) => $query->orderByDesc('year'),
                                )
                                ->searchable()
                                ->preload()
                                ->required()
                                ->columnSpan(1),

                            Select::make('school_id')
                                ->label('Scuola')
                                ->relationship(
                                    name: 'school',
                                    titleAttribute: 'description',
                                    modifyQueryUsing: fn($query) => $query->orderBy('sort'),
                                )
                                ->searchable()
                                ->preload()
                                ->required()
                                ->columnSpanFull(),
                        ]),

                    Section::make('Produzione')
                        ->columns(2)
                        ->columnSpan(1)
                        ->schema([
                            Select::make('status')
                                ->label('Stato')
                                ->options(Order::statusLabels())
                                ->default(Order::STATUS_NEW)
                                ->required()
                                ->columnSpan(1),

                            TextInput::make('quantity')
                                ->label('Quantità')
                                ->numeric()
                                ->minValue(1)
                                ->default(1)
                                ->required()
                                ->columnSpan(1),

                            DateTimePicker::make('deadline_collection')
                                ->label('Scadenza raccolta')
                                ->native(false)
                                ->nullable()
                                ->columnSpan(1),

                            DateTimePicker::make('deadline_annotation')
                                ->label('Scadenza correzioni')
                                ->native(false)
                                ->nullable()
                                ->columnSpan(1),
                        ]),

                        Section::make('Modello')
                            ->columns(2)
                            ->columnSpanFull()
                            ->schema([
                                Select::make('template_type_id')
                                    ->label('Modello Generico')
                                    ->options(fn(): array => TemplateType::query()
                                        ->active()
                                        ->orderBy('sort')
                                        ->pluck('description', 'id')
                                        ->all())
                                    ->searchable()
                                    ->preload()
                                    ->nullable()
                                    ->dehydrated(false)
                                    ->columnSpan(1),

                                TextInput::make('template_id')
                                    ->label('Modello Diario')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->nullable()
                                    ->helperText('Il Modello Diario viene istanziato tramite il bottone Istanzia Modello.')
                                    ->columnSpan(1),
                        ]),
                ]),

            AuditSection::make(
                statusLabel: 'Stato',
                statusLabels: Order::statusLabels(),
            ),
        ]);
    }
}
