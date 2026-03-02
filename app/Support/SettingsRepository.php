<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Setting;

final class SettingsRepository
{
    public static function getInt(string $name, int $default = 0): int
    {
        $value = self::getRawValue($name);

        if (is_array($value)) {
            $value = $value[0] ?? null;
        }

        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        if (is_string($value)) {
            $trimmed = trim($value);

            if ($trimmed === '') {
                return $default;
            }

            if (is_numeric($trimmed)) {
                return (int) $trimmed;
            }
        }

        return $default;
    }

    /**
     * @return array<int, string>
     */
    public static function getArray(string $name, array $default = []): array
    {
        $value = self::getRawValue($name);

        if (is_array($value)) {
            return self::cleanStringList($value) ?: $default;
        }

        if (is_string($value)) {
            $trimmed = trim($value);

            if ($trimmed === '') {
                return $default;
            }

            $decoded = json_decode($trimmed, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return self::cleanStringList($decoded) ?: $default;
            }

            $parts = preg_split('/[,;\n\r]+/', $trimmed) ?: [];
            return self::cleanStringList($parts) ?: $default;
        }

        if (is_numeric($value)) {
            return [(string) $value];
        }

        return $default;
    }

    private static function getRawValue(string $name): mixed
    {
        $record = Setting::query()
            ->where('description', $name)
            ->where('environment', Setting::ENV_PRODUCTION)
            ->where('is_active', true)
            ->where('status', '!=', Setting::STATUS_DELETED)
            ->first(['value']);

        return $record?->value;
    }

    /**
     * @param  array<int, mixed>  $items
     * @return array<int, string>
     */
    private static function cleanStringList(array $items): array
    {
        $out = [];

        foreach ($items as $item) {
            if ($item === null) {
                continue;
            }

            $s = trim((string) $item);

            if ($s === '') {
                continue;
            }

            $out[] = $s;
        }

        return array_values(array_unique($out));
    }
}
