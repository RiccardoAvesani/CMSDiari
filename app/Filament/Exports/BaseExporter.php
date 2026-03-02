<?php

namespace App\Filament\Exports;

use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

abstract class BaseExporter extends Exporter
{
    public static function getCompletedNotificationTitle(Export $export): string
    {
        return 'Esportazione completata';
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $successful = number_format((int) ($export->successful_rows ?? 0));
        $failed = number_format((int) ($export->failed_rows ?? 0));

        if ((int) ($export->failed_rows ?? 0) > 0) {
            return "Righe esportate {$successful}. Righe fallite {$failed}.";
        }

        return "Righe esportate {$successful}.";
    }

    public function getJobQueue(): ?string
    {
        return 'export';
    }
}
