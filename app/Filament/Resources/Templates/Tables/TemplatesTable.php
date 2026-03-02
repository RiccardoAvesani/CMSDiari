<?php

namespace App\Filament\Resources\Templates\Tables;

use App\Models\Template;
use App\Models\User;
use App\Filament\Resources\Templates\TemplateResource;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Notifications\Notification;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Throwable;

class TemplatesTable
{
    public static function configureTable(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->recordAction(null)
            ->defaultSort('sort')
            ->reorderable('sort', fn(): bool => (bool) (Auth::user()?->isinternal ?? false))
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
                    ->toggleable()
                    ->placeholder('-')
                    ->formatStateUsing(fn($state) => User::blankToNull($state)),

                TextColumn::make('templateType.description')
                    ->label('Modello Generico')
                    ->toggleable()
                    ->placeholder('-')
                    ->formatStateUsing(fn($state) => User::blankToNull($state)),

                TextColumn::make('pages_count')
                    ->label('Numero Pagine')
                    ->counts('pages')
                    ->numeric()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('-'),

                TextColumn::make('school.description')
                    ->label('Scuola')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('-')
                    ->formatStateUsing(fn($state) => User::blankToNull($state)),

                TextColumn::make('order.external_id')
                    ->label('Ordine')
                    ->toggleable()
                    ->placeholder('-')
                    ->formatStateUsing(fn($state) => User::blankToNull($state)),

                TextColumn::make('status')
                    ->label('Stato')
                    ->badge()
                    ->formatStateUsing(fn(?string $state): string => Template::statusLabel($state))
                    ->color(fn(?string $state): string => Template::statusColor($state))
                    ->sortable()
                    ->toggleable()
                    ->placeholder('-'),

                IconColumn::make('is_custom_finale')
                    ->label('Sezione finale')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true)
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
                    ->options(Template::statusLabels())
                    ->default(Template::STATUS_ACTIVE),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn(Template $record): bool => TemplateResource::canEdit($record)),

                Action::make('soft_delete')
                    ->label('Elimina')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn(Template $record): bool => User::canAdminOrInternal() && ($record->status ?? null) !== Template::STATUS_DELETED)
                    ->action(function (Template $record): void {
                        /** @var Template $record */
                        $record->updateStatus(Template::STATUS_DELETED);

                        Notification::make()
                            ->title('Modello Compilato eliminato')
                            ->success()
                            ->send();
                    }),

                Action::make('restore')
                    ->label('Ripristina')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn(Template $record): bool => User::canAdminOrInternal() && ($record->status ?? null) === Template::STATUS_DELETED)
                    ->action(function (Template $record): void {
                        /** @var Template $record */
                        $record->updateStatus(Template::STATUS_ACTIVE);

                        Notification::make()
                            ->title('Modello Compilato ripristinato')
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
                            $records->each(function (Template $record): void {
                                if (($record->status ?? null) === Template::STATUS_DELETED) {
                                    return;
                                }

                                $record->updateStatus(Template::STATUS_DELETED);
                            });
                        })
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('restore_bulk')
                        ->label('Ripristina selezionati')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn(): bool => User::canAdminOrInternal())
                        ->action(function (Collection $records): void {
                            $records->each(function (Template $record): void {
                                if (($record->status ?? null) !== Template::STATUS_DELETED) {
                                    return;
                                }

                                $record->updateStatus(Template::STATUS_ACTIVE);
                            });
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}
