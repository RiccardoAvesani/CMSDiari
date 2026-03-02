<?php

namespace App\Filament\Resources\Schools\Tables;

use App\Models\School;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class SchoolsTable
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
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->copyable()
                    ->placeholder('-')
                    ->formatStateUsing(fn($state) => User::blankToNull($state)),

                TextColumn::make('description')
                    ->label('Scuola')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        $like = '%' . $search . '%';

                        return $query->where(function (Builder $q) use ($like): void {
                            $q->where('schools.description', 'like', $like)
                                ->orWhere('schools.external_id', 'like', $like)
                                ->orWhere('schools.codice_fiscale', 'like', $like)
                                ->orWhereHas('locations', function (Builder $l) use ($like): void {
                                    $l->where('address', 'like', $like)
                                        ->orWhereHas('contacts', function (Builder $c) use ($like): void {
                                            $c->where('first_name', 'like', $like)
                                                ->orWhere('last_name', 'like', $like)
                                                ->orWhere('email', 'like', $like)
                                                ->orWhere('telephone', 'like', $like);
                                        });
                                });
                        });
                    })
                    ->toggleable()
                    ->placeholder('-')
                    ->formatStateUsing(fn($state) => User::blankToNull($state)),

                TextColumn::make('codice_fiscale')
                    ->label('Codice fiscale')
                    ->copyable()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('-')
                    ->formatStateUsing(fn($state) => User::blankToNull($state)),

                TextColumn::make('status')
                    ->label('Stato')
                    ->badge()
                    ->formatStateUsing(fn(?string $state): string => School::statusLabel($state))
                    ->color(fn(?string $state): string => School::statusColor($state))
                    ->sortable()
                    ->toggleable()
                    ->placeholder('-'),

                TextColumn::make('created_at')
                    ->label('Creata il')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('-'),

                TextColumn::make('updated_at')
                    ->label('Modificata il')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Stato')
                    ->options(School::statusLabels())
                    ->default(School::STATUS_ACTIVE),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),

                Action::make('soft_delete')
                    ->label('Elimina')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn(School $record): bool => User::canAdminOrInternal() && ($record->status ?? null) !== School::STATUS_DELETED)
                    ->action(function (School $record): void {
                        $record->update(['status' => School::STATUS_DELETED]);
                    })
                    ->successNotificationTitle('Scuola eliminata'),

                Action::make('restore')
                    ->label('Ripristina')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn(School $record): bool => User::canAdminOrInternal() && ($record->status ?? null) === School::STATUS_DELETED)
                    ->action(function (School $record): void {
                        $record->update(['status' => School::STATUS_ACTIVE]);
                    })
                    ->successNotificationTitle('Scuola ripristinata'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('soft_delete_bulk')
                        ->label('Elimina selezionate')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn(): bool => User::canAdminOrInternal())
                        ->action(function (Collection $records): void {
                            $records->each(function (School $record): void {
                                if (($record->status ?? null) === School::STATUS_DELETED) {
                                    return;
                                }

                                $record->update(['status' => School::STATUS_DELETED]);
                            });
                        })
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('restore_bulk')
                        ->label('Ripristina selezionate')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn(): bool => User::canAdminOrInternal())
                        ->action(function (Collection $records): void {
                            $records->each(function (School $record): void {
                                if (($record->status ?? null) !== School::STATUS_DELETED) {
                                    return;
                                }

                                $record->update(['status' => School::STATUS_ACTIVE]);
                            });
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}
