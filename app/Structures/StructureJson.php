<?php

declare(strict_types=1);

namespace App\Structures;

use JsonException;

final class StructureJson
{
    public static function encodePretty(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $value = trim($value);

            return $value !== '' ? $value : null;
        }

        $encoded = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $encoded === false ? null : $encoded;
    }

    /**
     * @return array{ok: bool, value: mixed, error: ?string}
     */
    public static function decode(?string $json): array
    {
        $json = is_string($json) ? trim($json) : '';

        if ($json === '') {
            return [
                'ok' => true,
                'value' => null,
                'error' => null,
            ];
        }

        try {
            $decoded = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

            return [
                'ok' => true,
                'value' => $decoded,
                'error' => null,
            ];
        } catch (JsonException $e) {
            return [
                'ok' => false,
                'value' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    public static function canonicalizeForCompare(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map([self::class, 'canonicalizeForCompare'], $value);
        }

        $out = [];
        $keys = array_keys($value);
        sort($keys);

        foreach ($keys as $key) {
            $out[$key] = self::canonicalizeForCompare($value[$key]);
        }

        return $out;
    }

    public static function onlyValuesChanged(mixed $baseStructure, mixed $candidateStructure): bool
    {
        $baseNormalized = self::normalizeStructureShapeForCompare($baseStructure);
        $candidateNormalized = self::normalizeStructureShapeForCompare($candidateStructure);

        $baseSanitized = StructureSanitizer::blankValues($baseNormalized);
        $candidateSanitized = StructureSanitizer::blankValues($candidateNormalized);

        $a = self::canonicalizeForCompare($baseSanitized);
        $b = self::canonicalizeForCompare($candidateSanitized);

        return json_encode($a) === json_encode($b);
    }

    private static function normalizeStructureShapeForCompare(mixed $structure): mixed
    {
        if (! is_array($structure)) {
            return $structure;
        }

        if (array_is_list($structure)) {
            return $structure;
        }

        $keys = array_keys($structure);

        // Caso: blocco singolo del tipo { "Titolo": { ... } }
        if (count($keys) === 1 && is_string($keys[0]) && trim($keys[0]) !== '') {
            return [$structure];
        }

        // Caso: struttura "grezza" del tipo { fields: [...] } (la impacchetto in un blocco singolo)
        if (array_key_exists('fields', $structure) && is_array($structure['fields'] ?? null)) {
            return [
                [
                    'Struttura' => $structure,
                ],
            ];
        }

        return $structure;
    }
}
