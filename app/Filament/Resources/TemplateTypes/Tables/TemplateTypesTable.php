<?php

namespace App\Filament\Resources\TemplateTypes\Tables;

use App\Models\TemplateType;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;

class TemplateTypesTable
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

                TextColumn::make('description')
                    ->label('Nome')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('-'),

                TextColumn::make('items_count')
                    ->label('Numero Pagine personalizzate')
                    ->counts('items')
                    ->numeric()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('-'),

                TextColumn::make('max_pages')
                    ->label('Numero massimo pagine personalizzabili')
                    ->numeric()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('-'),

                TextColumn::make('status')
                    ->label('Stato')
                    ->badge()
                    ->formatStateUsing(fn(?string $state): string => TemplateType::statusLabel($state))
                    ->color(fn(?string $state): string => TemplateType::statusColor($state))
                    ->sortable()
                    ->toggleable()
                    ->placeholder('-'),

                IconColumn::make('is_custom_finale')
                    ->label('Finale custom')
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
                    ->options(TemplateType::statusLabels())
                    ->default(TemplateType::STATUS_ACTIVE),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),

                Action::make('soft_delete')
                    ->label('Elimina')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn(TemplateType $record): bool => User::canAdminOrInternal() && ($record->status ?? null) !== TemplateType::STATUS_DELETED)
                    ->action(function (TemplateType $record): void {
                        $record->updateStatus(TemplateType::STATUS_DELETED);

                        Notification::make()
                            ->title('Modello Generico eliminato')
                            ->success()
                            ->send();
                    }),

                Action::make('restore')
                    ->label('Ripristina')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn(TemplateType $record): bool => User::canAdminOrInternal() && ($record->status ?? null) === TemplateType::STATUS_DELETED)
                    ->action(function (TemplateType $record): void {
                        $record->updateStatus(TemplateType::STATUS_ACTIVE);

                        Notification::make()
                            ->title('Modello Generico ripristinato')
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
                            $records->each(function (TemplateType $record): void {
                                if (($record->status ?? null) === TemplateType::STATUS_DELETED) {
                                    return;
                                }

                                $record->updateStatus(TemplateType::STATUS_DELETED);
                            });
                        })
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('restore_bulk')
                        ->label('Ripristina selezionati')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn(): bool => User::canAdminOrInternal())
                        ->action(function (Collection $records): void {
                            $records->each(function (TemplateType $record): void {
                                if (($record->status ?? null) !== TemplateType::STATUS_DELETED) {
                                    return;
                                }

                                $record->updateStatus(TemplateType::STATUS_ACTIVE);
                            });
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}
