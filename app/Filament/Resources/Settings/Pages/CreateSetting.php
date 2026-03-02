<?php

namespace App\Filament\Resources\Settings\Pages;

use App\Filament\Resources\Settings\SettingResource;
use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateSetting extends CreateRecord
{
    protected static string $resource = SettingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = $data['status'] ?? Setting::STATUS_ACTIVE;
        $data['is_active'] = $data['is_active'] ?? true;

        $name = (string) ($data['description'] ?? '');
        $data['value'] = SettingResource::normalizeValueForSave($name, $data['value'] ?? null);

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back_to_list')
                ->label('Torna alla Lista')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn(): string => SettingResource::getUrl('index')),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(),
            Action::make('create_and_exit')
                ->label('Crea ed esci')
                ->color('gray')
                ->action('createAndExit'),
            $this->getCancelFormAction(),
        ];
    }

    public function createAndExit(): void
    {
        $this->create();

        $this->redirect(SettingResource::getUrl('index'));
    }
}
