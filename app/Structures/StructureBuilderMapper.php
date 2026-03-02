<?php

declare(strict_types=1);

namespace App\Structures;

use RuntimeException;

final class StructureBuilderMapper
{
    /**
     * Builder storico a blocchi usato altrove nel progetto.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function structureToBuilder(mixed $structure): array
    {
        $blocks = self::normalizeToBlocks($structure);

        $builder = [];

        foreach ($blocks as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $title = array_key_first($entry);

            if (! is_string($title)) {
                continue;
            }

            $title = trim($title);

            if ($title === '') {
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

            $builder[] = [
                'title' => $title,
                'position' => is_numeric($data['position'] ?? null) ? (int) $data['position'] : null,
                'occurrence' => is_numeric($data['occurrence'] ?? null) ? (int) $data['occurrence'] : null,
                'fields' => self::normalizeFieldsArray($fields, stripValues: false),
            ];
        }

        return $builder;
    }

    /**
     * @param array<int, array<string, mixed>> $builder
     * @return array<int, array<string, mixed>>
     */
    public static function builderToStructure(array $builder): array
    {
        $structure = [];

        foreach ($builder as $block) {
            if (! is_array($block)) {
                continue;
            }

            $title = trim((string) ($block['title'] ?? ''));

            if ($title === '') {
                continue;
            }

            $data = [];

            if (is_numeric($block['position'] ?? null)) {
                $data['position'] = (int) $block['position'];
            }

            if (is_numeric($block['occurrence'] ?? null)) {
                $data['occurrence'] = (int) $block['occurrence'];
            }

            $fields = $block['fields'] ?? [];
            $data['fields'] = self::normalizeFieldsArray($fields, stripValues: false);

            $structure[] = [
                $title => $data,
            ];
        }

        return $structure;
    }

    /**
     * Per PageType estraggo tutti i campi della struttura ignorando i value.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function structureToFields(mixed $structure): array
    {
        $fields = [];

        $entries = self::normalizeToBlocks($structure);

        foreach ($entries as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $title = array_key_first($entry);

            if (! is_string($title)) {
                continue;
            }

            $title = trim($title);

            if ($title === '') {
                continue;
            }

            $data = $entry[$title] ?? null;

            if (! is_array($data)) {
                continue;
            }

            $blockFields = $data['fields'] ?? null;

            foreach (self::normalizeFieldsArray($blockFields, stripValues: true) as $f) {
                $fields[] = $f;
            }
        }

        return $fields;
    }

    /**
     * Per PageType costruisco una struttura valida a partire da un elenco di campi (value sempre rimossi).
     *
     * @param  array<int, array<string, mixed>>  $fields
     * @return array<string, mixed>
     */
    public static function fieldsToStructure(array $fields, string $title): array
    {
        $title = trim($title);

        if ($title === '') {
            $title = 'Tipologia Pagina';
        }

        return [
            $title => [
                'fields' => self::normalizeFieldsArray($fields, stripValues: true),
            ],
        ];
    }

    /**
     * Normalizzo la struttura di definizione PageType: tengo solo "fields" e rimuovo qualsiasi "value".
     *
     * @return array<int, array<string, mixed>>
     */
    public static function normalizeDefinitionStructure(mixed $structure): array
    {
        $blocks = self::normalizeToBlocks($structure);

        $normalized = [];

        foreach ($blocks as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $title = array_key_first($entry);

            if (! is_string($title)) {
                continue;
            }

            $title = trim($title);

            if ($title === '') {
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

            $normalized[] = [
                $title => [
                    'fields' => self::normalizeFieldsArray($fields, stripValues: true),
                ],
            ];
        }

        return $normalized;
    }

    public static function containsNonEmptyValues(mixed $data): bool
    {
        if (! is_array($data)) {
            return false;
        }

        foreach ($data as $key => $value) {
            if ($key === 'value') {
                if (self::isNonEmptyValue($value)) {
                    return true;
                }

                continue;
            }

            if (is_array($value) && self::containsNonEmptyValues($value)) {
                return true;
            }
        }

        return false;
    }

    private static function isNonEmptyValue(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_string($value)) {
            return trim($value) !== '';
        }

        if (is_array($value)) {
            return count($value) > 0;
        }

        return true;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function normalizeToBlocks(mixed $structure): array
    {
        if (! is_array($structure)) {
            return [];
        }

        if (array_is_list($structure)) {
            return $structure;
        }

        $keys = array_keys($structure);

        // Caso: blocco singolo del tipo { "Titolo": { ... } }
        if (count($keys) === 1 && is_string($keys[0]) && trim($keys[0]) !== '') {
            return [$structure];
        }

        // Caso: struttura "grezza" del tipo { fields: [...] }
        if (array_key_exists('fields', $structure) && is_array($structure['fields'] ?? null)) {
            return [
                [
                    'Struttura' => $structure,
                ],
            ];
        }

        return [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function normalizeFieldsArray(mixed $fields, bool $stripValues): array
    {
        if (! is_array($fields)) {
            return [];
        }

        $out = [];

        foreach ($fields as $field) {
            if (! is_array($field)) {
                continue;
            }

            $normalized = self::normalizeSingleField($field, $stripValues);

            if ($normalized === null) {
                continue;
            }

            $out[] = $normalized;
        }

        return $out;
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function normalizeSingleField(array $field, bool $stripValues): ?array
    {
        $label = array_key_exists('label', $field) ? $field['label'] : null;
        $label = is_string($label) ? trim($label) : null;

        $format = array_key_exists('format', $field) ? $field['format'] : null;
        $format = is_string($format) ? trim($format) : null;

        $maxCharactersKey = null;
        if (array_key_exists('max_characters_key', $field) && is_string($field['max_characters_key'])) {
            $maxCharactersKey = trim($field['max_characters_key']);
        }

        $maxCharacters = $field['max_characters'] ?? null;
        $maxCharacters = is_numeric($maxCharacters) ? (int) $maxCharacters : null;

        $maxSize = $field['max_size'] ?? null;
        $maxSize = is_numeric($maxSize) ? (int) $maxSize : null;

        $tableSize = array_key_exists('table_size', $field) ? $field['table_size'] : null;
        $tableSize = is_string($tableSize) ? trim($tableSize) : null;

        $normalized = [];

        if ($label !== null && $label !== '') {
            $normalized['label'] = $label;
        }

        if ($format !== null && $format !== '') {
            $normalized['format'] = $format;
        }

        if ($maxCharactersKey !== null && $maxCharactersKey !== '') {
            $normalized['max_characters_key'] = $maxCharactersKey;
        }

        if ($maxCharacters !== null) {
            $normalized['max_characters'] = $maxCharacters;
        }

        if ($maxSize !== null) {
            $normalized['max_size'] = $maxSize;
        }

        if ($tableSize !== null && $tableSize !== '') {
            $normalized['table_size'] = $tableSize;
        }

        if (! $stripValues && array_key_exists('value', $field)) {
            $normalized['value'] = $field['value'];
        }

        return $normalized;
    }
}
