<?php

declare(strict_types=1);

namespace App\Structures;

final class StructurePresenter
{
    /**
     * @return array{
     *   entries: array<int, array{title: string, meta: array<string, mixed>, fields: array<int, array<string, mixed>>}>,
     *   blocks: array<int, array{title: string, meta: array<string, mixed>, fields: array<int, array<string, mixed>>}>,
     *   fields: array<int, array<string, mixed>>,
     *   pretty_json: ?string,
     * }
     */
    public static function present(mixed $structure, ?array $constraints = null): array
    {
        $decoded = self::decode($structure);

        $entries = self::entries($decoded, $constraints);
        $flatFields = self::fields($decoded, $constraints);
        $prettyJson = self::prettyJson($decoded);

        return [
            'entries' => $entries,
            'blocks' => $entries, // compat: vecchie view/usi che si aspettano "blocks"
            'fields' => $flatFields,
            'pretty_json' => $prettyJson,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function fields(mixed $structure, ?array $constraints = null): array
    {
        $decoded = self::decode($structure);

        if (! is_array($decoded)) {
            return [];
        }

        $entries = self::normalizeEntries($decoded);

        $flat = [];

        foreach ($entries as $entryIndex => $entry) {
            if (! is_int($entryIndex) || ! is_array($entry)) {
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

            $normalizedIndex = 0;

            foreach ($fields as $fieldIndex => $field) {
                if (! is_int($fieldIndex) || ! is_array($field)) {
                    continue;
                }

                $normalized = self::normalizeField($field, $constraints, $normalizedIndex);
                $normalized['entry_index'] = $entryIndex;
                $normalized['field_index'] = $fieldIndex;
                $normalized['title'] = $title;

                $flat[] = $normalized;

                $normalizedIndex++;
            }
        }

        return $flat;
    }

    /**
     * @return array<int, array{title: string, meta: array<string, mixed>, fields: array<int, array<string, mixed>>}>
     */
    public static function entries(mixed $structure, ?array $constraints = null): array
    {
        if (! is_array($structure)) {
            return [];
        }

        $entries = self::normalizeEntries($structure);

        $out = [];

        foreach ($entries as $entryIndex => $entry) {
            if (! is_int($entryIndex) || ! is_array($entry)) {
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

            $meta = [];
            if (array_key_exists('position', $data)) {
                $meta['position'] = $data['position'];
            }
            if (array_key_exists('occurrence', $data)) {
                $meta['occurrence'] = $data['occurrence'];
            }

            $fields = $data['fields'] ?? null;

            if (! is_array($fields)) {
                $fields = [];
            }

            $normalizedFields = [];
            $normalizedIndex = 0;

            foreach ($fields as $fieldIndex => $field) {
                if (! is_int($fieldIndex) || ! is_array($field)) {
                    continue;
                }

                $normalized = self::normalizeField($field, $constraints, $normalizedIndex);
                $normalized['entry_index'] = $entryIndex;
                $normalized['field_index'] = $fieldIndex;
                $normalized['title'] = $title;

                $normalizedFields[] = $normalized;

                $normalizedIndex++;
            }

            $out[] = [
                'title' => $title,
                'meta' => $meta,
                'fields' => $normalizedFields,
            ];
        }

        return $out;
    }

    private static function decode(mixed $structure): mixed
    {
        if ($structure === null) {
            return null;
        }

        if (is_array($structure)) {
            return $structure;
        }

        if (is_string($structure)) {
            $structure = trim($structure);

            if ($structure === '') {
                return null;
            }

            $decoded = StructureJson::decode($structure);

            if (($decoded['ok'] ?? false) !== true) {
                return null;
            }

            return $decoded['value'] ?? null;
        }

        return null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function normalizeEntries(array $decoded): array
    {
        if (array_is_list($decoded)) {
            return $decoded;
        }

        if (array_key_exists('fields', $decoded) && is_array($decoded['fields'] ?? null)) {
            return [
                ['Struttura' => $decoded],
            ];
        }

        $out = [];

        foreach ($decoded as $k => $v) {
            if (! is_string($k)) {
                continue;
            }

            $k = trim($k);

            if ($k === '') {
                continue;
            }

            if (! is_array($v)) {
                continue;
            }

            $out[] = [$k => $v];
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    private static function normalizeField(array $field, ?array $constraints, int $normalizedIndex): array
    {
        $out = $field;

        $format = trim((string) ($field['format'] ?? StructureFieldFormats::FORMAT_TEXT));
        $out['format'] = $format !== '' ? mb_strtolower($format) : mb_strtolower(StructureFieldFormats::FORMAT_TEXT);

        $label = '';
        if (array_key_exists('label', $field) && is_string($field['label'])) {
            $label = trim($field['label']);
        }
        if ($label === '' && array_key_exists('name', $field) && is_string($field['name'])) {
            $label = trim($field['name']);
        }
        if ($label === '' && array_key_exists('description', $field) && is_string($field['description'])) {
            $label = trim($field['description']);
        }

        if ($label === '') {
            $label = self::fallbackLabel((string) $out['format'], $normalizedIndex);
        }

        $out['label'] = $label;

        $maxCharacters = $field['max_characters'] ?? null;
        if (! is_numeric($maxCharacters)) {
            $key = $field['max_characters_key'] ?? null;
            $key = is_string($key) ? trim($key) : null;

            if (is_array($constraints) && is_string($key) && $key !== '' && is_numeric($constraints[$key] ?? null)) {
                $maxCharacters = (int) $constraints[$key];
            } else {
                $maxCharacters = null;
            }
        } else {
            $maxCharacters = (int) $maxCharacters;
        }

        if (is_int($maxCharacters) && $maxCharacters <= 0) {
            $maxCharacters = null;
        }

        $out['max_characters'] = $maxCharacters;

        $maxSize = $field['max_size'] ?? null;
        if (is_numeric($maxSize)) {
            $maxSize = (int) $maxSize;
            if ($maxSize <= 0) {
                $maxSize = null;
            }
        } else {
            $maxSize = null;
        }

        $out['max_size'] = $maxSize;

        $tableSize = $field['table_size'] ?? null;
        if (is_string($tableSize)) {
            $tableSize = trim($tableSize);
            if ($tableSize === '') {
                $tableSize = null;
            }
        } else {
            $tableSize = null;
        }

        $out['table_size'] = $tableSize;

        $dims = null;
        if (is_string($tableSize)) {
            $dims = self::parseTableSize($tableSize);
        }

        if (is_array($dims)) {
            $out['table_dimensions'] = $dims;
        } else {
            if (! array_key_exists('table_dimensions', $out)) {
                $out['table_dimensions'] = null;
            }
        }

        if (! array_key_exists('value', $out)) {
            $out['value'] = null;
        }

        $formatLower = mb_strtolower((string) $out['format']);
        $out['is_empty_field'] = in_array($formatLower, ['riga vuota', 'tabella vuota'], true);

        return $out;
    }

    private static function fallbackLabel(string $format, int $normalizedIndex): string
    {
        $format = mb_strtolower(trim($format));

        $base = match ($format) {
            mb_strtolower(StructureFieldFormats::FORMAT_IMAGE), 'immagine' => 'Immagine',
            mb_strtolower(StructureFieldFormats::FORMAT_TINYMCE), 'tinymce' => 'Testo editor',
            mb_strtolower(StructureFieldFormats::FORMAT_EMPTY_LINE), 'riga vuota' => 'Riga vuota',
            mb_strtolower(StructureFieldFormats::FORMAT_EMPTY_TABLE), 'tabella vuota' => 'Tabella vuota',
            'file' => 'File',
            'messaggio', 'message' => 'Messaggio',
            default => 'Testo',
        };

        return $base . ' ' . ($normalizedIndex + 1);
    }

    /**
     * @return array{rows: int, cols: int}|null
     */
    private static function parseTableSize(string $tableSize): ?array
    {
        if (! preg_match('/^(\d+)\s*[xX]\s*(\d+)$/', $tableSize, $m)) {
            return null;
        }

        $rows = (int) ($m[1] ?? 0);
        $cols = (int) ($m[2] ?? 0);

        if ($rows <= 0 || $cols <= 0) {
            return null;
        }

        return [
            'rows' => $rows,
            'cols' => $cols,
        ];
    }

    private static function prettyJson(mixed $decoded): ?string
    {
        if ($decoded === null) {
            return null;
        }

        if (is_string($decoded)) {
            $decoded = trim($decoded);

            return $decoded !== '' ? $decoded : null;
        }

        $encoded = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $encoded !== false ? $encoded : null;
    }
}
