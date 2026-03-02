<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Actions\Templates\InstantiateTemplateForOrder;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\TemplateType;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Throwable;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    protected function afterCreate(): void
    {
        $state = $this->form->getState();

        $templateTypeId = $state['template_type_id'] ?? null;

        if (! $templateTypeId) {
            return;
        }

        if ($this->record->template_id) {
            return;
        }

        try {
            $templateType = TemplateType::query()
                ->whereKey($templateTypeId)
                ->first();

            if (! $templateType) {
                Notification::make()
                    ->title('Modello Generico non trovato')
                    ->danger()
                    ->send();

                return;
            }

            $template = app(InstantiateTemplateForOrder::class)->handle($this->record, $templateType);

            Notification::make()
                ->title('Modello Diario creato')
                ->body("Creato Modello Compilato con ID {$template->id} e generate le Pagine.")
                ->success()
                ->send();

            $this->record->refresh();
        } catch (Throwable $e) {
            Notification::make()
                ->title('Errore creazione Modello Diario')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
