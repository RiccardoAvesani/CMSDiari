<?php

declare(strict_types=1);

namespace App\Structures;

use App\Models\PageType;
use App\Models\TemplateType;

final class TemplateTypeStructureComposer
{
    public static function composeFromItems(array $items): array
    {
        $pageTypeIds = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $pageTypeId = $item['page_type_id'] ?? null;

            if (is_numeric($pageTypeId)) {
                $pageTypeIds[] = (int) $pageTypeId;
            }
        }

        $pageTypeIds = array_values(array_unique(array_filter($pageTypeIds)));

        if ($pageTypeIds === []) {
            return [];
        }

        $pageTypesById = PageType::query()
            ->whereIn('id', $pageTypeIds)
            ->get()
            ->keyBy('id');

        $out = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $pageTypeId = $item['page_type_id'] ?? null;
            $position = $item['position'] ?? null;

            if (! is_numeric($pageTypeId) || ! is_numeric($position)) {
                continue;
            }

            /** @var PageType|null $pageType */
            $pageType = $pageTypesById->get((int) $pageTypeId);

            if (! $pageType) {
                continue;
            }

            $entry = self::makeEntryFromPageType(
                pageType: $pageType,
                position: (int) $position,
            );

            if ($entry !== null) {
                $out[] = $entry;
            }
        }

        usort($out, function (array $a, array $b): int {
            $posA = self::extractPosition($a);
            $posB = self::extractPosition($b);

            return $posA <=> $posB;
        });

        return $out;
    }

    public static function composeFromTemplateType(?TemplateType $record): array
    {
        if (! $record) {
            return [];
        }

        $record->loadMissing('items.pageType');

        $items = [];

        foreach ($record->items as $item) {
            $items[] = [
                'page_type_id' => (int) ($item->page_type_id ?? 0),
                'position' => (int) ($item->position ?? 0),
            ];
        }

        return self::composeFromItems($items);
    }

    private static function makeEntryFromPageType(PageType $pageType, int $position): ?array
    {
        $decoded = StructureJson::decode($pageType->structure);

        $structure = $decoded['ok'] ? $decoded['value'] : null;

        $fields = [];

        if (is_array($structure) && array_is_list($structure)) {
            $first = $structure[0] ?? null;

            if (is_array($first)) {
                $firstTitle = array_key_first($first);

                if (is_string($firstTitle) && $firstTitle !== '') {
                    $data = $first[$firstTitle] ?? null;

                    if (is_array($data)) {
                        $rawFields = $data['fields'] ?? null;

                        if (is_array($rawFields) && array_is_list($rawFields)) {
                            $fields = $rawFields;
                        }
                    }
                }
            }
        }

        $title = trim((string) ($pageType->description ?? 'Tipologia'));

        if ($title === '') {
            $title = 'Tipologia';
        }

        return [
            $title => [
                'position' => $position,
                'occurrence' => null,
                'fields' => $fields,
            ],
        ];
    }

    private static function extractPosition(array $entry): int
    {
        $title = array_key_first($entry);

        if (! is_string($title) || $title === '') {
            return 0;
        }

        $data = $entry[$title] ?? null;

        if (! is_array($data)) {
            return 0;
        }

        $position = $data['position'] ?? null;

        return is_numeric($position) ? (int) $position : 0;
    }
}
