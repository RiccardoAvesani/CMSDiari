<?php

namespace App\Filament\Resources\Campaigns\Tables;

use App\Models\Campaign;
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

class CampaignTable
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

                TextColumn::make('year')
                    ->label('Anno')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->placeholder('-')
                    ->formatStateUsing(fn($state) => User::blankToNull($state)),

                TextColumn::make('description')
                    ->label('Descrizione')
                    ->searchable()
                    ->toggleable()
                    ->placeholder('-')
                    ->formatStateUsing(fn($state) => User::blankToNull($state)),

                TextColumn::make('status')
                    ->label('Stato')
                    ->badge()
                    ->formatStateUsing(fn(?string $state): string => Campaign::statusLabel($state))
                    ->color(fn(?string $state): string => Campaign::statusColor($state))
                    ->sortable()
                    ->toggleable()
                    ->placeholder('-'),

                TextColumn::make('created_at')
                    ->label('Creato il')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('-'),

                TextColumn::make('updated_at')
                    ->label('Aggiornato il')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Stato')
                    ->options(Campaign::statusLabels())
                    //->default(Campaign::STATUS_ACTIVE),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),

                Action::make('soft_delete')
                    ->label('Elimina')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn(Campaign $record): bool => User::canAdminOrInternal() && ($record->status ?? null) !== Campaign::STATUS_DELETED)
                    ->action(function (Campaign $record): void {
                        $record->update(['status' => Campaign::STATUS_DELETED]);
                    })
                    ->successNotificationTitle('Campagna eliminata'),

                Action::make('restore')
                    ->label('Ripristina')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn(Campaign $record): bool => User::canAdminOrInternal() && ($record->status ?? null) === Campaign::STATUS_DELETED)
                    ->action(function (Campaign $record): void {
                        $record->update(['status' => Campaign::STATUS_ACTIVE]);
                    })
                    ->successNotificationTitle('Campagna ripristinata'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('soft_delete_bulk')
                        ->label('Elimina selezionate')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn(): bool => User::canAdminOrInternal())
                        ->action(function (Collection $records): void {
                            $records->each(function (Campaign $record): void {
                                if (($record->status ?? null) === Campaign::STATUS_DELETED) {
                                    return;
                                }

                                $record->update(['status' => Campaign::STATUS_DELETED]);
                            });
                        })
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('restore_bulk')
                        ->label('Ripristina selezionate')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn(): bool => User::canAdminOrInternal())
                        ->action(function (Collection $records): void {
                            $records->each(function (Campaign $record): void {
                                if (($record->status ?? null) !== Campaign::STATUS_DELETED) {
                                    return;
                                }

                                $record->update(['status' => Campaign::STATUS_ACTIVE]);
                            });
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}
