<?php

namespace App\Filament\Resources\Settings\Pages;

use App\Filament\Exports\SettingExporter;
use App\Filament\Resources\Settings\SettingResource;
use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListSettings extends ListRecords
{
    protected static string $resource = SettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),

            ExportAction::make('export')
                ->label('Esporta')
                ->icon('heroicon-o-arrow-down-tray')
                ->exporter(SettingExporter::class),

            Action::make('restore_defaults')
                ->label('Ripristina valori predefiniti')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->requiresConfirmation()
                ->visible(function (): bool {
                    $user = Auth::user();

                    return (bool) $user && str_starts_with((string) $user->role, 'admin');
                })
                ->action(function (): void {
                    $defaults = SettingResource::defaultValues();

                    foreach ($defaults as $name => $defaultValue) {
                        $record = Setting::query()
                            ->where('description', $name)
                            ->where('environment', Setting::ENV_PRODUCTION)
                            ->first();

                        if (! $record) {
                            continue;
                        }

                        $record->update([
                            'value' => SettingResource::normalizeValueForSave($name, $defaultValue),
                            'isactive' => true,
                            'status' => Setting::STATUS_ACTIVE,
                        ]);
                    }

                    Notification::make()
                        ->title('Default ripristinati')
                        ->success()
                        ->send();
                }),
        ];
    }
}
