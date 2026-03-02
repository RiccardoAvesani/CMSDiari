<?php

namespace App\Filament\Resources\Settings\Tables;

use App\Filament\Resources\Settings\SettingResource;
use App\Models\Setting;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;

class SettingsTable
{
    public static function configureTable(Table $table): Table
    {
        $environmentLabels = Setting::environmentOptions();
        $permissionLabels = Setting::permissionOptions();

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

                TextColumn::make('description')
                    ->label('Nome')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-')
                    ->formatStateUsing(fn($state) => User::blankToNull($state))
                    ->toggleable(),

                TextColumn::make('instructions')
                    ->label('Descrizione')
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = trim(User::stateToString($column->getState()));

                        if ($state === '') {
                            return null;
                        }

                        $limit = $column->getCharacterLimit();

                        if ($limit !== null && mb_strlen($state) <= $limit) {
                            return null;
                        }

                        return $state;
                    })
                    ->toggleable()
                    ->placeholder('-')
                    ->formatStateUsing(fn($state) => User::blankToNull($state)),

                TextColumn::make('value')
                    ->label('Valore')
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = trim(self::stateToString($column->getState()));

                        if ($state === '') {
                            return null;
                        }

                        $limit = $column->getCharacterLimit();

                        if ($limit !== null && mb_strlen($state) <= $limit) {
                            return null;
                        }

                        return $state;
                    })
                    ->formatStateUsing(function ($state, Setting $record): string {
                        $value = SettingResource::formatValueForTable((string) ($record->description ?? ''), $state);

                        $value = trim(self::stateToString($value));

                        return $value !== '' ? $value : '-';
                    })
                    ->toggleable()
                    ->placeholder('-'),

                TextColumn::make('environment')
                    ->label('Ambiente')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('-')
                    ->formatStateUsing(fn(?string $state): string => $state ? ($environmentLabels[$state] ?? 'Sconosciuto') : '-'),

                TextColumn::make('permission')
                    ->label('Permessi')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('-')
                    ->formatStateUsing(fn($state): string => $state !== null ? ($permissionLabels[(string) $state] ?? 'Sconosciuto') : '-'),

                IconColumn::make('is_active')
                    ->label('Abilitata')
                    ->boolean()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('-'),

                TextColumn::make('status')
                    ->label('Stato')
                    ->badge()
                    ->formatStateUsing(fn(?string $state): string => Setting::statusLabel($state))
                    ->color(fn(?string $state): string => Setting::statusColor($state))
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
                    ->options(Setting::statusLabels())
                    ->default(Setting::STATUS_ACTIVE),

                SelectFilter::make('environment')
                    ->label('Ambiente')
                    ->options($environmentLabels),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn(Setting $record): bool => SettingResource::canEdit($record)),

                Action::make('soft_delete')
                    ->label('Elimina')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn(Setting $record): bool => User::canAdminOrInternal() && ! $record->isDeleted())
                    ->action(function (Setting $record): void {
                        $record->softDelete();

                        Notification::make()
                            ->title('Impostazione eliminata')
                            ->success()
                            ->send();
                    }),

                Action::make('restore')
                    ->label('Ripristina')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn(Setting $record): bool => User::canAdminOrInternal() && $record->isDeleted())
                    ->action(function (Setting $record): void {
                        $record->restore();

                        Notification::make()
                            ->title('Impostazione ripristinata')
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
                            $records->each(function (Setting $record): void {
                                if ($record->isDeleted()) {
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
                            $records->each(function (Setting $record): void {
                                if (! $record->isDeleted()) {
                                    return;
                                }

                                $record->restore();
                            });
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    private static function stateToString(mixed $state): string
    {
        if ($state === null) {
            return '';
        }

        if (is_string($state)) {
            return $state;
        }

        if (is_bool($state)) {
            return $state ? '1' : '0';
        }

        if (is_int($state) || is_float($state)) {
            return (string) $state;
        }

        if (is_array($state)) {
            if (array_is_list($state)) {
                return implode(', ', array_map(fn($v): string => self::stateToString($v), $state));
            }

            return (string) (json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');
        }

        if ($state instanceof \Stringable) {
            return (string) $state;
        }

        return '';
    }
}
