<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Actions\Templates\InstantiateTemplateForOrder;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Pages\PageResource;
use App\Filament\Resources\Templates\TemplateResource;
use App\Models\Order;
use App\Models\TemplateType;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back_to_list')
                ->label('Torna alla Lista')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn(): string => OrderResource::getUrl('index')),

            Action::make('open_template')
                ->label('Apri Modello Diario')
                ->icon('heroicon-o-rectangle-stack')
                ->color('gray')
                ->visible(fn(): bool => ! blank($this->record->template_id))
                ->url(fn(): string => TemplateResource::getUrl('view', ['record' => $this->record->template_id])),

            Action::make('open_pages')
                ->label('Apri Pagine')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->visible(fn(): bool => ! blank($this->record->template_id))
                ->url(fn(): string => PageResource::getUrl('index', [
                    'tableFilters' => [
                        'template_id' => [
                            'value' => $this->record->template_id,
                        ],
                    ],
                ])),

            EditAction::make()
                ->visible(fn(): bool => OrderResource::canEdit($this->record)),

            Action::make('instantiate_template')
                ->label('Istanzia Modello')
                ->icon('heroicon-o-squares-plus')
                ->color('primary')
                ->visible(fn(): bool => User::canAdminOrInternal() && blank($this->record->template_id) && $this->record->status !== Order::STATUS_DELETED)
                ->schema([
                    Select::make('template_type_id')
                        ->label('Modello')
                        ->options(fn(): array => TemplateType::query()
                            ->where('status', '!=', TemplateType::STATUS_DELETED)
                            ->orderBy('sort')
                            ->pluck('description', 'id')
                            ->all())
                        ->searchable()
                        ->preload()
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $templateType = TemplateType::query()
                        ->whereKey($data['template_type_id'])
                        ->firstOrFail();

                    $template = app(InstantiateTemplateForOrder::class)->handle($this->record, $templateType);

                    $this->record->update([
                        'template_id' => $template->id,
                    ]);

                    Notification::make()
                        ->title('Modello istanziato')
                        ->success()
                        ->send();

                    $this->redirect(OrderResource::getUrl('view', ['record' => $this->record]));
                }),

            Action::make('soft_delete')
                ->label('Elimina')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn(): bool => OrderResource::canDelete($this->record) && ! $this->record->isDeleted)
                ->action(function (): void {
                    /** @var Order $record */
                    $record = $this->record;

                    $record->softDelete();

                    Notification::make()
                        ->title('Ordine eliminato')
                        ->success()
                        ->send();

                    $this->redirect(OrderResource::getUrl('index'));
                }),

            Action::make('restore')
                ->label('Ripristina')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn(): bool => User::canAdminOrInternal() && $this->record->isDeleted)
                ->action(function (): void {
                    /** @var Order $record */
                    $record = $this->record;

                    $record->restore();

                    Notification::make()
                        ->title('Ordine ripristinato')
                        ->success()
                        ->send();

                    $this->redirect(OrderResource::getUrl('index'));
                }),
        ];
    }
}
