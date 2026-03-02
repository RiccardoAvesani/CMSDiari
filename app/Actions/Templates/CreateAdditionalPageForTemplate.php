<?php

namespace App\Actions\Templates;

use App\Models\Page;
use App\Models\PageType;
use App\Models\Template;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CreateAdditionalPageForTemplate
{
    public function handle(Template $template, int $pageTypeId, int $position, ?string $description = null): Page
    {
        return DB::transaction(function () use ($template, $pageTypeId, $position, $description): Page {
            $template = Template::query()
                ->lockForUpdate()
                ->whereKey($template->id)
                ->firstOrFail();

            $template->loadMissing('templateType.items.pageType');

            if (! $template->order_id) {
                throw new RuntimeException('Impossibile creare Pagina: manca order_id sul Modello Compilato.');
            }

            if (! $template->school_id) {
                throw new RuntimeException('Impossibile creare Pagina: manca school_id sul Modello Compilato.');
            }

            if (! $template->templateType) {
                throw new RuntimeException('Impossibile creare Pagina: manca il Modello Generico collegato.');
            }

            $allowedItem = $template->templateType->items
                ->firstWhere('page_type_id', $pageTypeId);

            if (! $allowedItem) {
                throw new RuntimeException('Tipologia Pagina non ammessa per questo Modello Compilato.');
            }

            $pageType = PageType::query()
                ->whereKey($pageTypeId)
                ->firstOrFail();

            $maxPages = (int) ($pageType->max_pages ?? 1);
            if ($maxPages <= 0) {
                $maxPages = 1;
            }

            $currentCount = (int) Page::query()
                ->where('template_id', $template->id)
                ->where('page_type_id', $pageTypeId)
                ->count();

            if ($currentCount >= $maxPages) {
                throw new RuntimeException("Numero massimo raggiunto: per questa Tipologia sono ammesse al massimo {$maxPages} Pagine.");
            }

            $suggestedPosition = $this->suggestNextPositionForType($template->id, $pageTypeId, (int) ($allowedItem->position ?? 1));

            $position = (int) $position;
            if ($position <= 0) {
                $position = $suggestedPosition;
            }

            if ($position <= 0) {
                $position = 1;
            }

            $this->shiftPagesForwardFromPosition($template->id, $position);

            $structureByPosition = $this->mapTemplateStructureByPosition($template->templateType->structure);
            $pageStructure = $structureByPosition[$position] ?? ($pageType->structure ?? []);

            $finalDescription = trim((string) ($description ?? ''));
            if ($finalDescription === '') {
                $finalDescription = (string) ($pageType->description ?? 'Pagina');
            }

            $page = Page::create([
                'template_id' => $template->id,
                'page_type_id' => $pageTypeId,
                'order_id' => $template->order_id,
                'school_id' => $template->school_id,

                'position' => $position,
                'sort' => $position,

                'description' => $finalDescription,

                // Snapshot dal padre (page_type)
                'page_type_description' => $pageType->description,
                'page_type_space' => $pageType->space,
                'page_type_max_pages' => $pageType->max_pages,
                'page_type_icon_url' => $pageType->icon_url,
                'page_type_structure' => $pageType->structure,

                'structure' => $pageStructure,
                'status' => Page::STATUS_ACTIVE,
            ]);

            Page::query()
                ->where('template_id', $template->id)
                ->update([
                    'sort' => DB::raw('position'),
                ]);

            return $page->refresh();
        });
    }

    private function suggestNextPositionForType(int $templateId, int $pageTypeId, int $fallbackPosition): int
    {
        $last = Page::query()
            ->where('template_id', $templateId)
            ->where('page_type_id', $pageTypeId)
            ->max('position');

        if (is_numeric($last) && (int) $last > 0) {
            return ((int) $last) + 1;
        }

        return $fallbackPosition > 0 ? $fallbackPosition : 1;
    }

    private function shiftPagesForwardFromPosition(int $templateId, int $fromPosition): void
    {
        $pagesToShift = Page::query()
            ->where('template_id', $templateId)
            ->where('position', '>=', $fromPosition)
            ->orderByDesc('position')
            ->lockForUpdate()
            ->get();

        foreach ($pagesToShift as $page) {
            $page->forceFill([
                'position' => ((int) $page->position) + 1,
                'sort' => (((int) $page->position) + 1),
            ])->save();
        }
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
