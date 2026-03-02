<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Pages\PageResource;
use App\Filament\Resources\Templates\TemplateResource;
use App\Models\TemplateType;
use App\Models\User;
use App\Actions\Templates\InstantiateTemplateForOrder;
use Filament\Forms\Components\Select;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditOrder extends EditRecord
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
                    'tableFilters[template_id][value]' => $this->record->template_id,
                ])),

            Action::make('instantiate_template')
                ->label('Istanzia Modello')
                ->icon('heroicon-o-squares-plus')
                ->color('primary')
                ->visible(fn(): bool => User::canAdminOrInternal() && blank($this->record->template_id) && ! $this->record->isDeleted())
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
                    $template_type = TemplateType::query()->whereKey($data['template_type_id'])->firstOrFail();

                    $template = app(InstantiateTemplateForOrder::class)->handle($this->record, $template_type);

                    $this->record->update([
                        'template_id' => $template->id,
                    ]);

                    Notification::make()
                        ->title('Modello istanziato')
                        ->success()
                        ->send();

                    $this->redirect(OrderResource::getUrl('edit', ['record' => $this->record]));
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

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            Action::make('save_and_exit')
                ->label('Salva ed esci')
                ->color('gray')
                ->action('saveAndExit'),
            $this->getCancelFormAction(),
        ];
    }

    public function saveAndExit(): void
    {
        $this->save();

        $this->redirect(OrderResource::getUrl('index'));
    }
}
