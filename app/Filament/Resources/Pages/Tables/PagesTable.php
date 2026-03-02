<?php

namespace App\Filament\Resources\Pages\Tables;

use App\Models\Page;
use App\Models\PageType;
use App\Models\School;
use App\Models\TemplateType;
use App\Models\Template;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class PagesTable
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

                TextColumn::make('template.templateType.description')
                    ->label('Modello Diario')
                    ->toggleable()
                    ->placeholder('-'),

                TextColumn::make('pageType.description')
                    ->label('Tipologia Pagina')
                    ->toggleable()
                    ->limit(30)
                    ->placeholder('-'),

                TextColumn::make('description')
                    ->label('Nome')
                    ->toggleable()
                    ->limit(30)
                    ->placeholder('-'),

                TextColumn::make('school.description')
                    ->label('Scuola')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('-'),

                TextColumn::make('order.external_id')
                    ->label('Ordine')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('-')
                    ->formatStateUsing(fn($state) => User::blankToNull($state)),

                TextColumn::make('position')
                    ->label('Posizione')
                    ->sortable()
                    ->toggleable()
                    ->placeholder('-'),

                TextColumn::make('status')
                    ->label('Stato')
                    ->badge()
                    ->formatStateUsing(fn(?string $state): string => Page::statusLabel($state))
                    ->color(fn(?string $state): string => Page::statusColor($state))
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
                    ->label('Modificato il')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Stato')
                    ->options(Page::statusLabels())
                    ->default(Page::STATUS_ACTIVE),

                SelectFilter::make('page_type_id')
                    ->label('Tipologia Pagina')
                    ->options(function (): array {
                        return PageType::query()
                            ->where('status', '!=', PageType::STATUS_DELETED)
                            ->orderBy('sort')
                            ->pluck('description', 'id')
                            ->all();
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        return $query->when(
                            filled($value),
                            fn(Builder $q) => $q->where('page_type_id', $value),
                        );
                    }),

                SelectFilter::make('order_id')
                    ->label('Ordine')
                    ->options(function (): array {
                        $user = Auth::user();

                        $pageQuery = Page::query();
                        if ($user instanceof User && str_starts_with((string) $user->role, 'external')) {
                            $schoolIds = $user->schools()->pluck('schools.id')->all();
                            $pageQuery->whereIn('school_id', $schoolIds);
                        }

                        $orderIds = $pageQuery
                            ->distinct()
                            ->pluck('order_id')
                            ->filter()
                            ->values()
                            ->all();

                        if (empty($orderIds)) {
                            return [];
                        }

                        return Order::query()
                            ->whereIn('id', $orderIds)
                            ->with(['school', 'template.templateType'])
                            ->orderBy('sort')
                            ->get()
                            ->mapWithKeys(function (Order $order): array {
                                $externalId = trim((string) ($order->external_id ?? ''));
                                $school = trim((string) ($order->school?->description ?? ''));
                                $templateType = trim((string) ($order->template?->templateType?->description ?? ''));

                                $label = $externalId !== '' ? $externalId : ('Ordine #' . $order->id);
                                if ($school !== '') {
                                    $label .= ' - ' . $school;
                                }
                                if ($templateType !== '') {
                                    $label = $templateType . ' - ' . $label;
                                }

                                return [$order->id => $label];
                            })
                            ->all();
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        return $query->when(
                            filled($value),
                            fn(Builder $q) => $q->where('order_id', $value),
                        );
                    }),

                SelectFilter::make('school_id')
                    ->label('Scuola')
                    ->options(function (): array {
                        $user = Auth::user();

                        $schoolQuery = School::query()->where('status', '!=', School::STATUS_DELETED);

                        if ($user instanceof User && str_starts_with((string) $user->role, 'external')) {
                            $schoolIds = $user->schools()->pluck('schools.id')->all();
                            $schoolQuery->whereIn('id', $schoolIds);
                        }

                        return $schoolQuery
                            ->orderBy('sort')
                            ->pluck('description', 'id')
                            ->all();
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        return $query->when(
                            filled($value),
                            fn(Builder $q) => $q->where('school_id', $value),
                        );
                    }),

                SelectFilter::make('template_type_id')
                    ->label('Modello Generico')
                    ->options(function (): array {
                        $user = Auth::user();

                        $pageQuery = Page::query();
                        if ($user instanceof User && str_starts_with((string) $user->role, 'external')) {
                            $schoolIds = $user->schools()->pluck('schools.id')->all();
                            $pageQuery->whereIn('school_id', $schoolIds);
                        }

                        $templateIds = $pageQuery
                            ->distinct()
                            ->pluck('template_id')
                            ->filter()
                            ->values()
                            ->all();

                        if (empty($templateIds)) {
                            return [];
                        }

                        $templateTypeIds = Template::query()
                            ->whereIn('id', $templateIds)
                            ->distinct()
                            ->pluck('template_type_id')
                            ->filter()
                            ->values()
                            ->all();

                        if (empty($templateTypeIds)) {
                            return [];
                        }

                        return TemplateType::query()
                            ->whereIn('id', $templateTypeIds)
                            ->where('status', '!=', TemplateType::STATUS_DELETED)
                            ->orderBy('sort')
                            ->pluck('description', 'id')
                            ->all();
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        return $query->when(
                            filled($value),
                            fn(Builder $q) => $q->whereHas('template', fn(Builder $t) => $t->where('template_type_id', $value)),
                        );
                    }),

                SelectFilter::make('template_id')
                    ->label('Modello Compilato')
                    ->options(function (): array {
                        $user = Auth::user();

                        $pageQuery = Page::query();
                        if ($user instanceof User && str_starts_with((string) $user->role, 'external')) {
                            $schoolIds = $user->schools()->pluck('schools.id')->all();
                            $pageQuery->whereIn('school_id', $schoolIds);
                        }

                        $templateIds = $pageQuery
                            ->distinct()
                            ->pluck('template_id')
                            ->filter()
                            ->values()
                            ->all();

                        if (empty($templateIds)) {
                            return [];
                        }

                        return Template::query()
                            ->whereIn('id', $templateIds)
                            ->with(['templateType', 'order'])
                            ->orderBy('sort')
                            ->get()
                            ->mapWithKeys(function (Template $template): array {
                                $templateTypeDescription = trim((string) ($template->templateType?->description ?? ''));
                                $orderExternalId = trim((string) ($template->order?->external_id ?? ''));

                                $label = $templateTypeDescription !== '' ? $templateTypeDescription : 'Modello';
                                if ($orderExternalId !== '') {
                                    $label .= ' - ' . $orderExternalId;
                                }

                                return [$template->id => $label];
                            })
                            ->all();
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        return $query->when(
                            filled($value),
                            fn(Builder $q) => $q->where('template_id', $value),
                        );
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn(Page $record): bool => \App\Filament\Resources\Pages\PageResource::canEdit($record)),

                Action::make('soft_delete')
                    ->label('Elimina')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn(Page $record): bool => User::canAdminOrInternal() && ! $record->isDeleted)
                    ->action(function (Page $record): void {
                        $record->softDelete();

                        Notification::make()
                            ->title('Pagina eliminata')
                            ->success()
                            ->send();
                    }),

                Action::make('restore')
                    ->label('Ripristina')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn(Page $record): bool => User::canAdminOrInternal() && $record->isDeleted)
                    ->action(function (Page $record): void {
                        $record->restore();

                        Notification::make()
                            ->title('Pagina ripristinata')
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
                            $records->each(function (Page $record): void {
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
                            $records->each(function (Page $record): void {
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