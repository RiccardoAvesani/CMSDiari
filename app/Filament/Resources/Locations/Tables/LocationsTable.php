<?php

namespace App\Filament\Resources\Locations\Tables;

use App\Models\Location;
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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class LocationsTable
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

                TextColumn::make('school.description')
                    ->label('Scuola')
                    ->toggleable()
                    ->placeholder('-')
                    ->formatStateUsing(fn($state) => User::blankToNull($state)),

                TextColumn::make('description')
                    ->label('Descrizione')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('-')
                    ->formatStateUsing(fn($state) => User::blankToNull($state)),

                TextColumn::make('address')
                    ->label('Indirizzo')
                    ->searchable()
                    ->toggleable()
                    ->placeholder('-')
                    ->formatStateUsing(fn($state) => User::blankToNull($state)),

                TextColumn::make('status')
                    ->label('Stato')
                    ->badge()
                    ->formatStateUsing(fn(?string $state): string => Location::statusLabel($state))
                    ->color(fn(?string $state): string => Location::statusColor($state))
                    ->sortable()
                    ->toggleable()
                    ->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Stato')
                    ->options(Location::statusLabels())
                    ->default(Location::STATUS_ACTIVE),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),

                Action::make('soft_delete')
                    ->label('Elimina')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn(Location $record): bool => User::canAdminOrInternal() && ! $record->isDeleted)
                    ->action(function (Location $record): void {
                        $record->softDelete();

                        Notification::make()
                            ->title('Sede eliminata')
                            ->success()
                            ->send();
                    }),

                Action::make('restore')
                    ->label('Ripristina')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn(Location $record): bool => User::canAdminOrInternal() && $record->isDeleted)
                    ->action(function (Location $record): void {
                        $record->restore();

                        Notification::make()
                            ->title('Sede ripristinata')
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('soft_delete_bulk')
                        ->label('Elimina selezionate')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn(): bool => User::canAdminOrInternal())
                        ->action(function (Collection $records): void {
                            $records->each(function (Location $record): void {
                                if ($record->isDeleted) {
                                    return;
                                }

                                $record->softDelete();
                            });
                        })
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('restore_bulk')
                        ->label('Ripristina selezionate')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn(): bool => User::canAdminOrInternal())
                        ->action(function (Collection $records): void {
                            $records->each(function (Location $record): void {
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
