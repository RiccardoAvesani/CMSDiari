<?php

declare(strict_types=1);

namespace App\Structures;

final class StructureValueMapper
{
    /**
     * Estrae una matrice di valori [blockIndex][fieldIndex] => value dalla struttura.
     *
     * @return array<int, array<int, mixed>>
     */
    public static function extractValues(mixed $structure): array
    {
        if (! is_array($structure)) {
            return [];
        }

        [$entries] = self::normalizeEntries($structure);

        $values = [];

        foreach ($entries as $b => $entry) {
            if (! is_int($b)) {
                continue;
            }

            if (! is_array($entry)) {
                continue;
            }

            $title = array_key_first($entry);

            if (! is_string($title)) {
                continue;
            }

            $data = $entry[$title] ?? null;

            if (! is_array($data)) {
                continue;
            }

            $fields = $data['fields'] ?? null;

            if (! is_array($fields)) {
                continue;
            }

            foreach ($fields as $f => $field) {
                if (! is_int($f)) {
                    continue;
                }

                if (! is_array($field)) {
                    continue;
                }

                $values[$b][$f] = $field['value'] ?? null;
            }
        }

        return $values;
    }

    /**
     * Applica i valori [blockIndex][fieldIndex] => value dentro la struttura originale.
     */
    public static function applyValues(mixed $structure, mixed $values): mixed
    {
        if (! is_array($structure) || ! is_array($values)) {
            return $structure;
        }

        [$entries, $shouldUnwrap] = self::normalizeEntries($structure);

        foreach ($entries as $b => $entry) {
            if (! is_int($b)) {
                continue;
            }

            if (! is_array($entry)) {
                continue;
            }

            $title = array_key_first($entry);

            if (! is_string($title)) {
                continue;
            }

            $data = $entry[$title] ?? null;

            if (! is_array($data)) {
                continue;
            }

            $fields = $data['fields'] ?? null;

            if (! is_array($fields)) {
                continue;
            }

            $blockValues = $values[$b] ?? null;

            if (! is_array($blockValues)) {
                continue;
            }

            foreach ($fields as $f => $field) {
                if (! is_int($f)) {
                    continue;
                }

                if (! array_key_exists($f, $blockValues)) {
                    continue;
                }

                if (! is_array($field)) {
                    continue;
                }

                $field['value'] = $blockValues[$f];

                $fields[$f] = $field;
            }

            $data['fields'] = $fields;
            $entry[$title] = $data;

            $entries[$b] = $entry;
        }

        if ($shouldUnwrap) {
            return $entries[0] ?? [];
        }

        return $entries;
    }

    /**
     * @param  array<mixed>  $structure
     * @return array{0: array<int, mixed>, 1: bool}
     */
    private static function normalizeEntries(array $structure): array
    {
        if ($structure === []) {
            return [[], false];
        }

        if (array_is_list($structure)) {
            return [array_values($structure), false];
        }

        $keys = array_keys($structure);

        if (count($keys) === 1 && is_string($keys[0])) {
            return [[$structure], true];
        }

        if (array_key_exists('fields', $structure)) {
            return [[
                [
                    'Struttura' => $structure,
                ],
            ], false];
        }

        return [[], false];
    }
}
