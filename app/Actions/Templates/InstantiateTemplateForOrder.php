<?php

namespace App\Actions\Templates;

use App\Models\Order;
use App\Models\Page;
use App\Models\Template;
use App\Models\TemplateType;
use Illuminate\Support\Facades\DB;

class InstantiateTemplateForOrder
{
    public function handle(Order $order, TemplateType $templateType): Template
    {
        return DB::transaction(function () use ($order, $templateType): Template {
            $templateType->loadMissing('items.pageType');

            $isCustomFinale = (bool) ($templateType->is_custom_finale ?? false);

            $template = new Template();
            $template->forceFill([
                'template_type_id' => $templateType->id,
                'order_id' => $order->id,
                'school_id' => $order->school_id ?? null,

                // Snapshot dal Modello Generico (editabili separatamente in futuro)
                'description' => $templateType->description . " - " . $order->external_id,
                'structure' => $templateType->structure,
                'size' => $templateType->size ?? null,
                'constraints' => $templateType->constraints ?? null,
                'max_pages' => $templateType->max_pages ?? null,

                'is_custom_finale' => $isCustomFinale,
                'is_giustificazioni' => $isCustomFinale ? (bool) ($templateType->is_giustificazioni ?? false) : false,
                'is_permessi' => $isCustomFinale ? (bool) ($templateType->is_permessi ?? false) : false,
                'is_visite' => $isCustomFinale ? (bool) ($templateType->is_visite ?? false) : false,

                'status' => Template::STATUS_ACTIVE,
            ]);
            $template->save();

            $structureByPosition = $this->mapTemplateStructureByPosition($templateType->structure);

            $items = $templateType->items
                ->sortBy(fn($item) => (int) ($item->position ?? 0))
                ->unique('page_type_id');

            foreach ($items as $item) {
                $position = (int) ($item->position ?? 0);
                $pageType = $item->pageType;

                $pageStructure = $structureByPosition[$position] ?? ($pageType?->structure ?? []);

                Page::create([
                    'page_type_id' => $item->page_type_id,
                    'template_id' => $template->id,
                    'order_id' => $order->id,
                    'school_id' => $order->school_id ?? null,
                    'position' => $position,
                    'sort' => $position,

                    'description' => $pageType?->description,

                    // Snapshot dal padre (page_type)
                    'page_type_description' => $pageType?->description,
                    'page_type_space' => $pageType?->space,
                    'page_type_max_pages' => $pageType?->max_pages,
                    'page_type_icon_url' => $pageType?->icon_url,
                    'page_type_structure' => $pageType?->structure,

                    'structure' => $pageStructure,
                    'status' => Page::STATUS_ACTIVE,
                ]);
            }

            if (blank($order->template_id)) {
                $order->update(['template_id' => $template->id]);
            }

            return $template->refresh();
        });
    }

    private function mapTemplateStructureByPosition(mixed $templateTypeStructure): array
    {
        if (! is_array($templateTypeStructure)) {
            return [];
        }

        $map = [];

        foreach ($templateTypeStructure as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $pageTypeDescription = array_key_first($entry);

            if (! is_string($pageTypeDescription) || $pageTypeDescription === '') {
                continue;
            }

            $data = $entry[$pageTypeDescription] ?? null;

            if (! is_array($data)) {
                continue;
            }

            $position = $data['position'] ?? null;

            if (! is_numeric($position)) {
                continue;
            }

            $map[(int) $position] = $entry;
        }

        return $map;
    }
}
