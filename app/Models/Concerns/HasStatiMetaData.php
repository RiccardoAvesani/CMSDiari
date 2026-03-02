<?php

namespace App\Models\Concerns;

trait HasStatiMetaData
{
    /**
     * Ogni Model che usa questo trait dovrebbe definire:
     *
     * public const STATI_META = [
     *     'active' => ['label' => 'Attivo', 'color' => 'success'],
     *     'deleted' => ['label' => 'Eliminato', 'color' => 'danger'],
     * ];
     */
    public static function statusMeta(): array
    {
        $constName = static::class . '::STATI_META';

        if (defined($constName)) {
            $meta = constant($constName);

            if (is_array($meta)) {
                return $meta;
            }
        }

        return [
            'active' => ['label' => 'Attivo', 'color' => 'success'],
            'deleted' => ['label' => 'Eliminato', 'color' => 'danger'],
        ];
    }

    public static function statusLabels(): array
    {
        $labels = [];

        foreach (static::statusMeta() as $status => $meta) {
            if (! is_string($status) || $status === '') {
                continue;
            }

            $label = $meta['label'] ?? null;

            if (! is_string($label) || $label === '') {
                $label = $status;
            }

            $labels[$status] = $label;
        }

        return $labels;
    }

    public static function statusColors(): array
    {
        $colors = [];

        foreach (static::statusMeta() as $status => $meta) {
            if (! is_string($status) || $status === '') {
                continue;
            }

            $color = $meta['color'] ?? null;

            if (! is_string($color) || $color === '') {
                $color = 'gray';
            }

            $colors[$status] = $color;
        }

        return $colors;
    }

    public static function statusLabel(?string $status): string
    {
        $status = self::normalizeStatus($status);

        if ($status === null) {
            return '-';
        }

        return static::statusLabels()[$status] ?? $status;
    }

    public static function statusColor(?string $status): string
    {
        $status = self::normalizeStatus($status);

        if ($status === null) {
            return 'gray';
        }

        return static::statusColors()[$status] ?? 'gray';
    }

    private static function normalizeStatus(?string $status): ?string
    {
        if ($status === null) {
            return null;
        }

        $status = trim($status);

        return $status === '' ? null : $status;
    }
}
