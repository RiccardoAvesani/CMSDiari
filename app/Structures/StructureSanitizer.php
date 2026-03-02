<?php

declare(strict_types=1);

namespace App\Structures;

final class StructureSanitizer
{
    /**
     * Sbianca tutte le proprietà 'value' dei field presenti nella struttura.
     */
    public static function blankValues(mixed $structure): mixed
    {
        if (! is_array($structure)) {
            return $structure;
        }

        return self::blankValuesRecursive($structure);
    }

    private static function blankValuesRecursive(array $data): array
    {
        if (array_key_exists('fields', $data) && is_array($data['fields'])) {
            foreach ($data['fields'] as $i => $field) {
                if (! is_array($field)) {
                    continue;
                }

                $field['value'] = '';
                $data['fields'][$i] = $field;
            }
        }

        foreach ($data as $key => $value) {
            if (! is_array($value)) {
                continue;
            }

            $data[$key] = self::blankValuesRecursive($value);
        }

        return $data;
    }
}
