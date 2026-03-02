<?php

namespace App\Actions\Templates;

use App\Models\Page;
use App\Models\PageType;
use App\Models\Template;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RegeneratePagesForTemplate
{
    public const REGENERATION_MODE_PURGE = 'purge';
    public const REGENERATION_MODE_ADD_ONLY = 'add_only';
    public const REGENERATION_MODE_ADD_AND_TRIM = 'add_and_trim';

    public function handle(
        Template $template,
        string $regenerationMode = self::REGENERATION_MODE_PURGE,
    ): Template {
        $template->refresh();

        if (! $template->order_id) {
            throw new RuntimeException('Impossibile rigenerare: manca order_id sul Modello Compilato.');
        }

        if (! $template->school_id) {
            throw new RuntimeException('Impossibile rigenerare: manca school_id sul Modello Compilato.');
        }

        $allowedModes = [
            self::REGENERATION_MODE_PURGE,
            self::REGENERATION_MODE_ADD_ONLY,
            self::REGENERATION_MODE_ADD_AND_TRIM,
        ];

        if (! in_array($regenerationMode, $allowedModes, true)) {
            throw new RuntimeException('Modalità rigenerazione non valida.');
        }

        if (Auth::check() && (! User::canAdminOrInternal())) {
            throw new RuntimeException('Non autorizzato a rigenerare le Pagine.');
        }

        return DB::transaction(function () use ($template, $regenerationMode): Template {
            $template = Template::query()
                ->lockForUpdate()
                ->whereKey($template->id)
                ->firstOrFail();

            $template->loadMissing('order', 'templateType.items.pageType');

            if (! $template->templateType) {
                throw new RuntimeException('Impossibile rigenerare: il Modello Compilato non ha un Modello Generico collegato.');
            }

            $isCustomFinale = (bool) ($template->templateType->is_custom_finale ?? false);

            $template->description = $template->templateType->description . ' - ' . ($template->order?->external_id ?? '');
            $template->structure = $template->templateType->structure;

            $template->size = $template->templateType->size ?? null;
            $template->constraints = $template->templateType->constraints ?? null;
            $template->max_pages = $template->templateType->max_pages ?? null;

            $template->is_custom_finale = $isCustomFinale;
            $template->is_giustificazioni = $isCustomFinale ? (bool) ($template->templateType->is_giustificazioni ?? false) : false;
            $template->is_permessi = $isCustomFinale ? (bool) ($template->templateType->is_permessi ?? false) : false;
            $template->is_visite = $isCustomFinale ? (bool) ($template->templateType->is_visite ?? false) : false;

            $template->save();

            $items = $template->templateType->items
                ->sortBy(fn($item) => (int) ($item->position ?? 0))
                ->unique('page_type_id')
                ->values();

            $structureByPosition = $this->mapTemplateStructureByPosition($template->templateType->structure);

            if ($regenerationMode === self::REGENERATION_MODE_PURGE) {
                Page::query()
                    ->where('template_id', $template->id)
                    ->delete();

                foreach ($items as $item) {
                    $position = (int) ($item->position ?? 0);
                    if ($position <= 0) {
                        $position = 1;
                    }

                    $pageType = $item->pageType;

                    $pageStructure = $structureByPosition[$position] ?? ($pageType?->structure ?? []);

                    Page::create([
                        'template_id' => $template->id,
                        'page_type_id' => $item->page_type_id,
                        'order_id' => $template->order_id,
                        'school_id' => $template->school_id,

                        'position' => $position,
                        'sort' => $position,

                        'description' => $pageType?->description,

                        'page_type_description' => $pageType?->description,
                        'page_type_space' => $pageType?->space,
                        'page_type_max_pages' => $pageType?->max_pages,
                        'page_type_icon_url' => $pageType?->icon_url,
                        'page_type_structure' => $pageType?->structure,

                        'structure' => $pageStructure,
                        'status' => Page::STATUS_ACTIVE,
                    ]);
                }

                $this->syncPagesSortToPosition($template->id);

                return $template->refresh();
            }

            foreach ($items as $item) {
                $pageTypeId = (int) ($item->page_type_id ?? 0);
                if ($pageTypeId <= 0) {
                    continue;
                }

                $alreadyExists = Page::query()
                    ->where('template_id', $template->id)
                    ->where('page_type_id', $pageTypeId)
                    ->exists();

                if ($alreadyExists) {
                    continue;
                }

                $position = (int) ($item->position ?? 0);
                if ($position <= 0) {
                    $position = 1;
                }

                $this->shiftPagesForwardFromPosition($template->id, $position);

                $pageType = $item->pageType;

                $pageStructure = $structureByPosition[$position] ?? ($pageType?->structure ?? []);

                Page::create([
                    'template_id' => $template->id,
                    'page_type_id' => $pageTypeId,
                    'order_id' => $template->order_id,
                    'school_id' => $template->school_id,

                    'position' => $position,
                    'sort' => $position,

                    'description' => $pageType?->description,

                    'page_type_description' => $pageType?->description,
                    'page_type_space' => $pageType?->space,
                    'page_type_max_pages' => $pageType?->max_pages,
                    'page_type_icon_url' => $pageType?->icon_url,
                    'page_type_structure' => $pageType?->structure,

                    'structure' => $pageStructure,
                    'status' => Page::STATUS_ACTIVE,
                ]);
            }

            if ($regenerationMode === self::REGENERATION_MODE_ADD_AND_TRIM) {
                $this->trimPagesByPageTypeMaxPages($template->id);
                $this->trimPagesByTemplateMaxPages($template);
            }

            $this->syncPagesSortToPosition($template->id);

            return $template->refresh();
        });
    }

    private function trimPagesByPageTypeMaxPages(int $templateId): void
    {
        $pageTypeIds = Page::query()
            ->where('template_id', $templateId)
            ->pluck('page_type_id')
            ->unique()
            ->values()
            ->all();

        if ($pageTypeIds === []) {
            return;
        }

        $pageTypes = PageType::query()
            ->whereIn('id', $pageTypeIds)
            ->get()
            ->keyBy('id');

        foreach ($pageTypeIds as $pageTypeId) {
            $pageTypeId = (int) $pageTypeId;
            if ($pageTypeId <= 0) {
                continue;
            }

            $pageType = $pageTypes->get($pageTypeId);
            $max = max(1, (int) ($pageType?->max_pages ?? 1));

            $count = (int) Page::query()
                ->where('template_id', $templateId)
                ->where('page_type_id', $pageTypeId)
                ->count();

            if ($count <= $max) {
                continue;
            }

            $excess = $count - $max;

            $idsToDelete = Page::query()
                ->where('template_id', $templateId)
                ->where('page_type_id', $pageTypeId)
                ->orderByDesc('position')
                ->limit($excess)
                ->pluck('id')
                ->all();

            if ($idsToDelete === []) {
                continue;
            }

            Page::query()
                ->whereIn('id', $idsToDelete)
                ->delete();
        }
    }

    private function trimPagesByTemplateMaxPages(Template $template): void
    {
        $max = (int) ($template->max_pages ?? 0);

        if ($max <= 0) {
            return;
        }

        $count = (int) Page::query()
            ->where('template_id', $template->id)
            ->count();

        if ($count <= $max) {
            return;
        }

        $excess = $count - $max;

        $idsToDelete = Page::query()
            ->where('template_id', $template->id)
            ->orderByDesc('position')
            ->limit($excess)
            ->pluck('id')
            ->all();

        if ($idsToDelete === []) {
            return;
        }

        Page::query()
            ->whereIn('id', $idsToDelete)
            ->delete();
    }

    private function shiftPagesForwardFromPosition(int $templateId, int $fromPosition): void
    {
        $fromPosition = (int) $fromPosition;

        if ($fromPosition <= 0) {
            $fromPosition = 1;
        }

        $pagesToShift = Page::query()
            ->where('template_id', $templateId)
            ->where('position', '>=', $fromPosition)
            ->orderByDesc('position')
            ->lockForUpdate()
            ->get();

        foreach ($pagesToShift as $page) {
            $newPosition = ((int) $page->position) + 1;

            $page->forceFill([
                'position' => $newPosition,
                'sort' => $newPosition,
            ])->save();
        }
    }

    private function syncPagesSortToPosition(int $templateId): void
    {
        Page::query()
            ->where('template_id', $templateId)
            ->update([
                'sort' => DB::raw('position'),
            ]);
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
