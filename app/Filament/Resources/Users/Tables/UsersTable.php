<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class UsersTable
{
    public static function configureTable(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->recordAction(null)
            ->defaultSort('sort')
            ->reorderable('sort', fn(): bool => User::canAdminOrInternal(Auth::user()))
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                ImageColumn::make('avatar_url')
                    ->label('Avatar')
                    ->circular()
                    ->checkFileExistence(false)
                    ->getStateUsing(function (User $record): string {
                        if (! empty($record->avatar_url)) {
                            return (string) $record->avatar_url;
                        }

                        return route('avatars.initials', $record) . '?v=' . ($record->updated_at?->timestamp ?? time());
                    })
                    ->disk(fn(string $state): ?string => str_starts_with($state, 'http') ? null : 'public')
                    ->visibility('public')
                    ->toggleable(),

                TextColumn::make('full_name')
                    ->label('Nome')
                    ->state(fn(User $record): string => (string) $record->full_name)
                    ->searchable(['first_name', 'last_name'])
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('-')
                    ->formatStateUsing(fn($state) => User::blankToNull($state)),

                TextColumn::make('role')
                    ->label('Ruolo')
                    ->badge()
                    ->formatStateUsing(fn(?string $state): string => User::roleLabel($state))
                    ->color(fn(?string $state): string => User::roleColor($state))
                    ->sortable()
                    ->toggleable()
                    ->placeholder('-'),

                TextColumn::make('status')
                    ->label('Stato')
                    ->badge()
                    ->formatStateUsing(fn(?string $state): string => User::statusLabel($state))
                    ->color(fn(?string $state): string => User::statusColor($state))
                    ->sortable()
                    ->toggleable()
                    ->placeholder('-'),

                TextColumn::make('company')
                    ->label('Azienda')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_by.full_name')
                    ->label('Creato da')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->state(fn(User $record): string => User::formatUserName($record->createdBy))
                    ->placeholder('-'),

                TextColumn::make('created_at')
                    ->label('Creato il')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('-'),

                TextColumn::make('updated_at')
                    ->label('Modificato il')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('-'),

                TextColumn::make('updated_by.full_name')
                    ->label('Modificato da')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->state(fn(User $record): string => User::formatUserName($record->updatedBy))
                    ->placeholder('-'),

                TextColumn::make('updated_at')
                    ->label('Modificato il')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Ruolo')
                    ->options(User::roleOptions()),

                SelectFilter::make('status')
                    ->label('Stato')
                    ->options(User::statusLabels())
                    ->default(User::STATUS_ACTIVE),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),

                Action::make('soft_delete')
                    ->label('Elimina')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn(User $record): bool => User::canAdminOrInternal() && ! $record->isDeleted)
                    ->action(function (User $record): void {
                        $record->softDelete();

                        Notification::make()
                            ->title('Utente eliminato')
                            ->success()
                            ->send();
                    }),

                Action::make('restore')
                    ->label('Ripristina')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn(User $record): bool => User::canAdminOrInternal() && $record->isDeleted)
                    ->action(function (User $record): void {
                        $record->restore();

                        Notification::make()
                            ->title('Utente ripristinato')
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
                            $records->each(function (User $record): void {
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
                            $records->each(function (User $record): void {
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
