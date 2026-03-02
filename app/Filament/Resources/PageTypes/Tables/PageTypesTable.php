<?php

namespace App\Filament\Resources\PageTypes\Tables;

use App\Models\PageType;
use App\Models\Page;
use App\Models\Template;
use App\Models\TemplateType;
use App\Models\Order;
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
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PageTypesTable
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
                    ->limit(30)
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('-'),

                TextColumn::make('space')
                    ->label('Spazio')
                    ->sortable()
                    ->toggleable()
                    ->placeholder('-'),

                TextColumn::make('max_pages')
                    ->label('Max occorrenze')
                    ->numeric()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('-'),

                TextColumn::make('status')
                    ->label('Stato')
                    ->badge()
                    ->formatStateUsing(fn(?string $state): string => PageType::statusLabel($state))
                    ->color(fn(?string $state): string => PageType::statusColor($state))
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
                    ->options(PageType::statusLabels())
                    ->default(PageType::STATUS_ACTIVE),

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
                            fn(Builder $q) => $q->whereKey($value),
                        );
                    }),

                SelectFilter::make('order_id')
                    ->label('Ordine')
                    ->options(function (): array {
                        $pageQuery = self::basePagesQueryForOptions();

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
                            fn(Builder $q) => $q->whereHas('pages', fn(Builder $p) => $p->where('order_id', $value)),
                        );
                    }),

                SelectFilter::make('school_id')
                    ->label('Scuola')
                    ->options(function (): array {
                        $pageQuery = self::basePagesQueryForOptions();

                        $schoolIds = $pageQuery
                            ->distinct()
                            ->pluck('school_id')
                            ->filter()
                            ->values()
                            ->all();

                        if (empty($schoolIds)) {
                            return [];
                        }

                        return School::query()
                            ->whereIn('id', $schoolIds)
                            ->where('status', '!=', School::STATUS_DELETED)
                            ->orderBy('sort')
                            ->pluck('description', 'id')
                            ->all();
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        return $query->when(
                            filled($value),
                            fn(Builder $q) => $q->whereHas('pages', fn(Builder $p) => $p->where('school_id', $value)),
                        );
                    }),

                SelectFilter::make('template_type_id')
                    ->label('Modello Generico')
                    ->options(function (): array {
                        $pageQuery = self::basePagesQueryForOptions();

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
                            fn(Builder $q) => $q
                                ->whereHas('pages', fn(Builder $p) => $p
                                ->whereHas('template', fn(Builder $t) => $t
                                ->where('template_type_id', $value))),
                        );
                    }),

                SelectFilter::make('template_id')
                    ->label('Modello Compilato')
                    ->options(function (): array {
                        $pageQuery = self::basePagesQueryForOptions();

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
                            fn(Builder $q) => $q->whereHas('pages', fn(Builder $p) => $p->where('template_id', $value)),
                        );
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),

                Action::make('soft_delete')
                    ->label('Elimina')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn(PageType $record): bool => User::canAdminOrInternal() && (($record->status ?? null) !== PageType::STATUS_DELETED))
                    ->action(function (PageType $record): void {
                        $record->softDelete();
                    })
                    ->successNotificationTitle('Tipologia Pagina eliminata'),

                Action::make('restore')
                    ->label('Ripristina')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn(PageType $record): bool => User::canAdminOrInternal() && (($record->status ?? null) === PageType::STATUS_DELETED))
                    ->action(function (PageType $record): void {
                        $record->restore();
                    })
                    ->successNotificationTitle('Tipologia Pagina ripristinata'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('soft_delete_bulk')
                        ->label('Elimina selezionate')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn(): bool => User::canAdminOrInternal())
                        ->action(function (Collection $records): void {
                            $records->each(function (PageType $record): void {
                                if (($record->status ?? null) === PageType::STATUS_DELETED) {
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
                            $records->each(function (PageType $record): void {
                                if (($record->status ?? null) !== PageType::STATUS_DELETED) {
                                    return;
                                }

                                $record->restore();
                            });
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    private static function basePagesQueryForOptions(): Builder
    {
        /** @var User|null $user */
        $user = Auth::user();
        /** @var Page|null $user */
        $query = Page::query();

        if ($user instanceof User && str_starts_with((string) $user->role, 'external')) {
            $schoolIds = $user->schools()->pluck('schools.id')->all();
            $query->whereIn('school_id', $schoolIds);
        }

        return $query;
    }
}
