<?php

namespace App\Filament\Resources\Invitations\Tables;

use App\Actions\Invitations\SendInvitation;
use App\Models\Invitation;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Throwable;

class InvitationsTable
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
                    ->toggleable(),

                TextColumn::make('user.full_name')
                    ->label('Nome')
                    ->toggleable()
                    ->placeholder('-'),

                TextColumn::make('status')
                    ->label('Stato')
                    ->badge()
                    ->formatStateUsing(fn(?string $state): string => Invitation::statusLabel($state))
                    ->color(fn(?string $state): string => Invitation::statusColor($state))
                    ->sortable()
                    ->toggleable()
                    ->placeholder('-'),

                TextColumn::make('sent_at')
                    ->label('Inviato il')
                    ->dateTime('d/m/Y H:i')
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
                    ->options(Invitation::statusLabels()),

                SelectFilter::make('role')
                    ->label('Ruolo')
                    ->options(User::roleOptions()),

                Filter::make('email')
                    ->label('Email')
                    ->schema([
                        TextInput::make('email')
                            ->label('Email contiene'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $email = $data['email'] ?? null;

                        return $query->when(
                            filled($email),
                            fn(Builder $q) => $q->where('email', 'like', '%' . $email . '%'),
                        );
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),

                Action::make('send')
                    ->label('Invia')
                    ->icon('heroicon-o-paper-airplane')
                    ->requiresConfirmation()
                    ->modalHeading("Spedire l'Invito via E-mail?")
                    ->modalDescription("L'email verrà inviata e l'Invito verrà impostato in stato Inviato.")
                    ->visible(function (Invitation $record): bool {
                        return $record->status === Invitation::STATUS_READY
                            && $record->sent_at === null
                            && $record->canBeOpened();
                    })
                    ->action(function (Invitation $record): void {
                        try {
                            app(SendInvitation::class)->handle($record);

                            Notification::make()
                                ->title('Invito inviato')
                                ->body("Email inviata per {$record->email}.")
                                ->success()
                                ->send();
                        } catch (Throwable $e) {
                            Notification::make()
                                ->title('Invio fallito')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('soft_delete')
                    ->label('Elimina')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn(Invitation $record): bool => User::canAdminOrInternal() && (($record->status ?? null) !== Invitation::STATUS_DELETED))
                    ->action(function (Invitation $record): void {
                        $record->softDelete();

                        Notification::make()
                            ->title('Invito eliminato')
                            ->success()
                            ->send();
                    }),

                Action::make('restore')
                    ->label('Ripristina')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn(Invitation $record): bool => User::canAdminOrInternal() && (($record->status ?? null) === Invitation::STATUS_DELETED))
                    ->action(function (Invitation $record): void {
                        $record->restore();

                        Notification::make()
                            ->title('Invito ripristinato')
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('send_bulk')
                        ->label('Invia selezionati')
                        ->icon('heroicon-o-paper-airplane')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $sent = 0;
                            $skipped = 0;

                            foreach ($records as $record) {
                                if (! $record instanceof Invitation) {
                                    $skipped++;
                                    continue;
                                }

                                if ($record->status !== Invitation::STATUS_READY || $record->sent_at !== null || ! $record->canBeOpened()) {
                                    $skipped++;
                                    continue;
                                }

                                try {
                                    app(SendInvitation::class)->handle($record);
                                    $sent++;
                                } catch (Throwable) {
                                    $skipped++;
                                }
                            }

                            Notification::make()
                                ->title('Invio completato')
                                ->body("Inviati: {$sent}. Saltati: {$skipped}.")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('soft_delete_bulk')
                        ->label('Elimina selezionati')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn(): bool => User::canAdminOrInternal())
                        ->action(function (Collection $records): void {
                            $records->each(function (Invitation $record): void {
                                if (($record->status ?? null) === Invitation::STATUS_DELETED) {
                                    return;
                                }

                                $record->update([
                                    'status' => Invitation::STATUS_DELETED,
                                ]);
                            });
                        })
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('restore_bulk')
                        ->label('Ripristina selezionati')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn(): bool => User::canAdminOrInternal())
                        ->action(function (Collection $records): void {
                            $records->each(function (Invitation $record): void {
                                if (($record->status ?? null) !== Invitation::STATUS_DELETED) {
                                    return;
                                }

                                $record->update([
                                    'status' => self::getRestoredStatus($record),
                                ]);
                            });
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    private static function getRestoredStatus(Invitation $record): string
    {
        if (! empty($record->user_id)) {
            return Invitation::STATUS_ACTIVE;
        }

        if (! empty($record->registered_at)) {
            return Invitation::STATUS_REGISTERED;
        }

        if (! empty($record->sent_at)) {
            return Invitation::STATUS_INVITED;
        }

        return Invitation::STATUS_READY;
    }
}
