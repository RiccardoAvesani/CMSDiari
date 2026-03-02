<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Models\Order;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class OrdersTable
{
    public static function configureTable(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->recordAction(null)
            ->defaultSort('sort')
            ->reorderable('sort', User::canAdminOrInternal())
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('-'),

                TextColumn::make('external_id')
                    ->label('ID ETB')
                    ->sortable()
                    ->toggleable()
                    ->copyable()
                    ->placeholder('-')
                    ->formatStateUsing(fn($state) => User::blankToNull($state)),

                TextColumn::make('template.description')
                    ->label('Modello')
                    ->searchable()
                    ->toggleable()
                    ->placeholder('-')
                    ->formatStateUsing(function ($state): ?string {
                        $value = User::blankToNull($state);

                        if ($value === null) {
                            return null;
                        }

                        return '<span class="font-semibold text-primary-600 dark:text-primary-400">' . e($value) . '</span>';
                    })
                    ->html()
                    ->weight(FontWeight::Bold),

                TextColumn::make('school.description')
                    ->label('Scuola')
                    ->searchable()
                    ->toggleable()
                    ->placeholder('-')
                    ->limit(50)
                    ->formatStateUsing(fn($state) => User::blankToNull($state)),

                TextColumn::make('campaign.description')
                    ->label('Campagna')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('-')
                    ->formatStateUsing(fn($state) => User::blankToNull($state)),

                TextColumn::make('campaign.year')
                    ->label('Anno')
                    ->toggleable()
                    ->placeholder('-')
                    ->formatStateUsing(fn($state) => User::blankToNull($state)),

                TextColumn::make('quantity')
                    ->label('Quantità')
                    ->sortable()
                    ->toggleable()
                    ->placeholder('-'),

                TextColumn::make('deadline_collection')
                    ->label('Scad. raccolta')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable()
                    ->placeholder('-'),

                TextColumn::make('deadline_annotation')
                    ->label('Scad. correzioni')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable()
                    ->placeholder('-'),

                TextColumn::make('status')
                    ->label('Stato')
                    ->badge()
                    ->formatStateUsing(fn(?string $state): string => Order::statusLabel($state))
                    ->color(fn(?string $state): string => Order::statusColor($state))
                    ->sortable()
                    ->toggleable()
                    ->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Stato')
                    ->options(Order::statusLabels()),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),

                Action::make('soft_delete')
                    ->label('Elimina')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn(Order $record): bool => User::canAdminOrInternal() && ! $record->isDeleted)
                    ->action(function (Order $record): void {
                        $record->softDelete();

                        Notification::make()
                            ->title('Ordine eliminato')
                            ->success()
                            ->send();
                    }),

                Action::make('restore')
                    ->label('Ripristina')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn(Order $record): bool => User::canAdminOrInternal() && $record->isDeleted)
                    ->action(function (Order $record): void {
                        $record->restore();

                        Notification::make()
                            ->title('Ordine ripristinato')
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('soft_delete_bulk')
                        ->label('Elimina selezionati')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn(): bool => User::canAdminOrInternal())
                        ->action(function (Collection $records): void {
                            $records->each(function (Order $record): void {
                                if ($record->isDeleted) {
                                    return;
                                }

                                $record->softDelete();
                            });
                        })
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('restore_bulk')
                        ->label('Ripristina selezionati')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn(): bool => User::canAdminOrInternal())
                        ->action(function (Collection $records): void {
                            $records->each(function (Order $record): void {
                                if (! $record->isDeleted) {
                                    return;
                                }

                                $record->restore();
                            });
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}
