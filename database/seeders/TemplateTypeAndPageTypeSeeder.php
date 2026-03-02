<?php

namespace Database\Seeders;

use App\Models\PageType;
use App\Models\TemplateType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TemplateTypeAndPageTypeSeeder extends Seeder
{
    private const DEFAULT_SPACE = 1.0;
    private const DEFAULT_MAX_UPLOAD_MB = 20;

    public function run(): void
    {
        DB::transaction(function (): void {
            $userId = User::query()->orderBy('sort')->value('id');

            $catalog = $this->pageTypeCatalog();

            foreach ($catalog as $pageTypeDescription => $definition) {
                PageType::query()->updateOrCreate(
                    ['description' => $pageTypeDescription],
                    [
                        'description' => $pageTypeDescription,
                        'space' => self::DEFAULT_SPACE,
                        'max_pages' => (int) $definition['max_pages'],
                        'structure' => [
                            $pageTypeDescription => [
                                'fields' => $this->normalizeFields($definition['fields'] ?? []),
                            ],
                        ],
                        'icon_url' => null,
                        'sort' => 0,
                        'status' => PageType::STATUS_ACTIVE,
                        'created_by' => $userId,
                        'updated_by' => $userId,
                    ],
                );
            }

            foreach ($this->templateTypeDefinitions() as $templateTypeDescription => $templateDefinition) {
                $size = (string) ($templateDefinition['size'] ?? TemplateType::SIZE_M);
                $constraints = TemplateType::defaultConstraintsForSize($size);

                if (isset($templateDefinition['constraints']) && is_array($templateDefinition['constraints'])) {
                    $constraints = array_replace($constraints, $templateDefinition['constraints']);
                }

                $templateType = TemplateType::query()->updateOrCreate(
                    ['description' => $templateTypeDescription],
                    [
                        'description' => $templateTypeDescription,
                        'size' => $size,
                        'constraints' => $constraints,
                        'structure' => [],
                        'max_pages' => null,
                        'sort' => 0,
                        'status' => TemplateType::STATUS_ACTIVE,
                        'is_custom_finale' => (bool) ($templateDefinition['is_custom_finale'] ?? false),
                        'is_giustificazioni' => false,
                        'is_permessi' => false,
                        'is_visite' => false,
                        'created_by' => $userId,
                        'updated_by' => $userId,
                    ],
                );

                $templateType->items()->detach();

                $position = 1;

                foreach ($templateDefinition['blocks'] as $block) {
                    $pageTypeDescription = (string) $block['page_type'];
                    $maxPagesInTemplate = (int) $block['max_pages'];

                    $pageType = PageType::query()
                        ->where('description', $pageTypeDescription)
                        ->firstOrFail();

                    $occurrencesInPivot = $maxPagesInTemplate <= 2 ? $maxPagesInTemplate : 1;

                    for ($i = 1; $i <= $occurrencesInPivot; $i++) {
                        $templateType->items()->attach($pageType->id, [
                            'position' => $position,
                            'created_by' => $userId,
                            'updated_by' => $userId,
                        ]);

                        $position++;
                    }
                }

                $templateType->load('items');

                $occurrenceCounters = [];
                $templateStructure = [];
                $customPagesCount = 0;

                foreach ($templateType->items->sortBy('pivot.position') as $pageType) {
                    if (! $pageType instanceof PageType) {
                        continue;
                    }

                    $pageTypeDescription = (string) $pageType->description;
                    $occurrenceCounters[$pageTypeDescription] = ($occurrenceCounters[$pageTypeDescription] ?? 0) + 1;

                    $entry = $pageType->structure;

                    if (! is_array($entry) || ! array_key_exists($pageTypeDescription, $entry)) {
                        $entry = [
                            $pageTypeDescription => [
                                'fields' => [],
                            ],
                        ];
                    }

                    $entry = $this->resolveEntryConstraints($entry, $constraints);

                    $entry[$pageTypeDescription]['occurrence'] = (int) $occurrenceCounters[$pageTypeDescription];
                    $entry[$pageTypeDescription]['position'] = (int) $pageType->pivot->position;

                    $templateStructure[] = $entry;

                    if ($this->entryHasValueFields($pageTypeDescription, $entry)) {
                        $customPagesCount++;
                    }
                }

                $templateType->forceFill([
                    'structure' => $templateStructure,
                    'max_pages' => $customPagesCount,
                    'updated_by' => $userId,
                ])->save();
            }
        });
    }

    private function resolveEntryConstraints(array $entry, array $constraints): array
    {
        $pageTypeDescription = array_key_first($entry);

        if (! is_string($pageTypeDescription) || $pageTypeDescription === '') {
            return $entry;
        }

        $fields = $entry[$pageTypeDescription]['fields'] ?? null;

        if (! is_array($fields)) {
            return $entry;
        }

        foreach ($fields as $i => $field) {
            if (! is_array($field)) {
                continue;
            }

            $key = $field['max_characters_key'] ?? null;

            if (! is_string($key) || $key === '') {
                continue;
            }

            if (! array_key_exists($key, $constraints)) {
                continue;
            }

            $entry[$pageTypeDescription]['fields'][$i]['max_characters'] = (int) $constraints[$key];
        }

        return $entry;
    }

    private function entryHasValueFields(string $pageTypeDescription, array $entry): bool
    {
        $fields = $entry[$pageTypeDescription]['fields'] ?? null;

        if (! is_array($fields)) {
            return false;
        }

        foreach ($fields as $field) {
            if (is_array($field) && array_key_exists('value', $field)) {
                return true;
            }
        }

        return false;
    }

    private function normalizeFields(array $fields): array
    {
        $normalized = [];

        foreach ($fields as $field) {
            if (! is_array($field)) {
                continue;
            }

            $label = trim((string) ($field['label'] ?? ''));

            if ($label === '') {
                continue;
            }

            $formatRaw = trim((string) ($field['format'] ?? ''));
            $formatLower = Str::lower($formatRaw);

            $isEmptyField = in_array($formatLower, ['riga vuota', 'tabella vuota'], true);

            $isImage =
                Str::contains($formatLower, 'immagine')
                || ($formatLower === '' && Str::contains(Str::lower($label), 'foto'))
                || ($formatLower === '' && Str::contains(Str::lower($label), 'logo'))
                || ($formatLower === '' && Str::contains(Str::lower($label), 'immagine'));

            $format = $formatRaw;

            if ($isImage && $format === '') {
                $format = 'immagine';
                $formatLower = 'immagine';
            }

            $row = [
                'label' => $label,
                'format' => $format,
            ];

            if ($formatLower === 'tabella vuota') {
                $tableSize = $field['table_size'] ?? null;

                if (is_string($tableSize) && trim($tableSize) !== '') {
                    $row['table_size'] = trim($tableSize);
                }
            }

            if ($isImage) {
                $row['max_size'] = self::DEFAULT_MAX_UPLOAD_MB;
            } elseif (! $isEmptyField) {
                $maxCharactersKey = $field['max_characters_key'] ?? null;

                if (is_string($maxCharactersKey) && $maxCharactersKey !== '') {
                    $row['max_characters_key'] = $maxCharactersKey;
                } else {
                    $maxCharacters = $field['max_characters'] ?? null;

                    if (is_numeric($maxCharacters)) {
                        $row['max_characters'] = (int) $maxCharacters;
                    }
                }
            }

            if (! $isEmptyField) {
                $row['value'] = '';
            }

            $normalized[] = $row;
        }

        return $normalized;
    }

    private function pageTypeCatalog(): array
    {
        return [
            'Dati scuola' => [
                'max_pages' => 1,
                'fields' => [
                    ['label' => 'Intestazione Scuola', 'format' => 'testo', 'max_characters' => 150],
                    ['label' => 'Foto', 'format' => 'immagine'],
                    ['label' => 'Logo 1', 'format' => 'immagine'],
                    ['label' => 'Logo 2', 'format' => 'immagine'],
                    ['label' => 'Logo 3', 'format' => 'immagine'],
                    ['label' => 'Indirizzo', 'format' => 'testo', 'max_characters' => 400],
                ],
            ],
            'Presentazione Scuola' => [
                'max_pages' => 1,
                'fields' => [
                    ['label' => 'Titolo', 'format' => 'testo', 'max_characters_key' => 'title'],
                    ['label' => 'Testo', 'format' => 'testo', 'max_characters_key' => 'text_main'],
                    ['label' => 'Foto', 'format' => 'immagine'],
                ],
            ],
            'Orari uffici' => [
                'max_pages' => 1,
                'fields' => [
                    ['label' => 'Titolo', 'format' => 'testo', 'max_characters_key' => 'title'],
                    ['label' => 'Informazioni', 'format' => 'testo', 'max_characters_key' => 'text_main'],
                ],
            ],
            'Dati studente' => [
                'max_pages' => 1,
                'fields' => [
                    ['label' => 'Nome', 'format' => 'riga vuota'],
                    ['label' => 'Cognome', 'format' => 'riga vuota'],
                    ['label' => 'Luogo e data di nascita', 'format' => 'riga vuota'],
                    ['label' => 'Scuola', 'format' => 'riga vuota'],
                    ['label' => 'Classe', 'format' => 'riga vuota'],
                    ['label' => 'Sezione', 'format' => 'riga vuota'],
                    ['label' => 'Indirizzo', 'format' => 'riga vuota'],
                    ['label' => 'Telefono', 'format' => 'riga vuota'],
                    ['label' => 'E-mail', 'format' => 'riga vuota'],
                    ['label' => 'Firme genitori/tutori', 'format' => 'riga vuota'],
                    ['label' => 'Note', 'format' => 'testo', 'max_characters_key' => 'note'],
                ],
            ],
            'Calendario scolastico' => [
                'max_pages' => 1,
                'fields' => [
                    ['label' => 'Titolo', 'format' => 'testo', 'max_characters_key' => 'title'],
                    ['label' => "Delibera consiglio d'Istituto", 'format' => 'testo', 'max_characters' => 200],
                    ['label' => 'Inizio lezioni', 'format' => 'testo', 'max_characters' => 200],
                    ['label' => 'Termine lezioni', 'format' => 'testo', 'max_characters' => 200],
                    ['label' => 'Festività', 'format' => 'testo', 'max_characters_key' => 'calendar_line'],
                    ['label' => 'Sospensioni', 'format' => 'testo', 'max_characters_key' => 'calendar_line'],
                    ['label' => 'Vacanze natalizie', 'format' => 'testo', 'max_characters_key' => 'calendar_line'],
                    ['label' => 'Vacanze pasquali', 'format' => 'testo', 'max_characters_key' => 'calendar_line'],
                ],
            ],
            'PTOF' => [
                'max_pages' => 10,
                'fields' => [
                    ['label' => 'Titolo', 'format' => 'testo', 'max_characters_key' => 'title'],
                    ['label' => 'Testo', 'format' => 'testo', 'max_characters_key' => 'text_main'],
                ],
            ],
            'Regolamenti (Istituto, Disciplina, Cellulari...)' => [
                'max_pages' => 10,
                'fields' => [
                    ['label' => 'Titolo', 'format' => 'testo', 'max_characters_key' => 'title'],
                    ['label' => 'Testo', 'format' => 'testo', 'max_characters_key' => 'text_main'],
                ],
            ],
            'Patto educativo (Primaria, Secondaria...)' => [
                'max_pages' => 10,
                'fields' => [
                    ['label' => 'Titolo', 'format' => 'testo', 'max_characters_key' => 'title'],
                    ['label' => 'Testo', 'format' => 'testo', 'max_characters_key' => 'text_main'],
                ],
            ],
            'Tabella orari provvisorio x 2' => [
                'max_pages' => 2,
                'fields' => [
                    ['label' => 'Tabella settimanale', 'format' => 'tabella vuota', 'table_size' => '9x6'],
                    ['label' => 'Note', 'format' => 'testo', 'max_characters_key' => 'note'],
                ],
            ],
            'Tabella orario definitivo x1' => [
                'max_pages' => 2,
                'fields' => [
                    ['label' => 'Tabella settimanale', 'format' => 'tabella vuota', 'table_size' => '9x6'],
                    ['label' => 'Note', 'format' => 'testo', 'max_characters_key' => 'note'],
                ],
            ],
            'Tabella Colloqui' => [
                'max_pages' => 1,
                'fields' => [
                    ['label' => 'Tabella settimanale', 'format' => 'tabella vuota', 'table_size' => '9x6'],
                    ['label' => 'Note', 'format' => 'testo', 'max_characters_key' => 'note'],
                ],
            ],
            'Tabella Libri di testo' => [
                'max_pages' => 1,
                'fields' => [
                    ['label' => 'Tabella per Materia', 'format' => 'tabella vuota', 'table_size' => 'max 15x4'],
                    ['label' => 'Note', 'format' => 'testo', 'max_characters_key' => 'note'],
                ],
            ],
            'Tabella Elenco materiale' => [
                'max_pages' => 1,
                'fields' => [
                    ['label' => 'Tabella per Materia', 'format' => 'tabella vuota', 'table_size' => 'max 15x4'],
                    ['label' => 'Note', 'format' => 'testo', 'max_characters_key' => 'note'],
                ],
            ],
            'Tabella Interrogazioni' => [
                'max_pages' => 1,
                'fields' => [
                    ['label' => 'Tabella per Materia', 'format' => 'tabella vuota', 'table_size' => 'max 15x4'],
                    ['label' => 'Note', 'format' => 'testo', 'max_characters_key' => 'note'],
                ],
            ],
            'Tabella Compagni di classe' => [
                'max_pages' => 5,
                'fields' => [
                    ['label' => 'Tabella per Alunno', 'format' => 'tabella vuota', 'table_size' => 'max 15x4'],
                    ['label' => 'Note', 'format' => 'testo', 'max_characters_key' => 'note'],
                ],
            ],
            'Tabella Disciplina x1' => [
                'max_pages' => 5,
                'fields' => [
                    ['label' => 'Titolo', 'format' => 'testo', 'max_characters_key' => 'title'],
                    ['label' => 'Note', 'format' => 'testo', 'max_characters_key' => 'note'],
                ],
            ],
            'Tabella Disciplina x2' => [
                'max_pages' => 5,
                'fields' => [
                    ['label' => 'Titolo', 'format' => 'testo', 'max_characters_key' => 'title'],
                    ['label' => 'Note', 'format' => 'testo', 'max_characters_key' => 'note'],
                ],
            ],
            'Comunicazioni Scuola Famiglia' => [
                'max_pages' => 5,
                'fields' => [
                    ['label' => 'Note', 'format' => 'testo', 'max_characters_key' => 'note'],
                ],
            ],
            'Note' => [
                'max_pages' => 30,
                'fields' => [
                    ['label' => 'Note', 'format' => 'testo', 'max_characters_key' => 'note'],
                ],
            ],
            'Plessi' => [
                'max_pages' => 6,
                'fields' => [
                    ['label' => 'Intestazione Scuola', 'format' => 'testo', 'max_characters' => 150],
                    ['label' => 'Foto', 'format' => 'immagine'],
                    ['label' => 'Logo', 'format' => 'immagine'],
                    ['label' => 'Indirizzo', 'format' => 'testo', 'max_characters' => 400],
                ],
            ],
            'Autorizzazioni / deleghe con segno di taglio x 1' => [
                'max_pages' => 10,
                'fields' => [
                    ['label' => 'Titolo', 'format' => 'testo', 'max_characters_key' => 'title'],
                    ['label' => 'Testo', 'format' => 'testo', 'max_characters_key' => 'text_main'],
                ],
            ],
            'Autorizzazioni / deleghe con segno di taglio x 2' => [
                'max_pages' => 10,
                'fields' => [
                    ['label' => 'Titolo', 'format' => 'testo', 'max_characters_key' => 'title'],
                    ['label' => 'Testo', 'format' => 'testo', 'max_characters_key' => 'text_short'],
                ],
            ],
            'Pagina bianca - retro autorizzazioni / deleghe con taglio' => [
                'max_pages' => 10,
                'fields' => [],
            ],
            'Gallery immagini' => [
                'max_pages' => 15,
                'fields' => [
                    ['label' => 'Titolo', 'format' => 'testo', 'max_characters_key' => 'title'],
                    ['label' => 'Foto', 'format' => 'immagine'],
                ],
            ],
            'Pagina libera componibile' => [
                'max_pages' => 15,
                'fields' => [
                    ['label' => 'Titolo', 'format' => 'TinyMCE', 'max_characters_key' => 'text_main'],
                ],
            ],
            'Giochi' => [
                'max_pages' => 30,
                'fields' => [
                    ['label' => 'Immagine', 'format' => 'immagine'],
                ],
            ],
            'Appunti' => [
                'max_pages' => 30,
                'fields' => [
                    ['label' => 'Note', 'format' => 'testo', 'max_characters_key' => 'note'],
                ],
            ],
            "Autorizzazione visite guidate tutto l'anno con segno di taglio x 1" => [
                'max_pages' => 1,
                'fields' => [
                    ['label' => 'Note', 'format' => 'testo', 'max_characters_key' => 'note'],
                ],
            ],
            'Benestare uscita didattica e viaggio istr. con segno di taglio x 2' => [
                'max_pages' => 15,
                'fields' => [
                    ['label' => 'Note', 'format' => 'testo', 'max_characters_key' => 'note'],
                ],
            ],
            'Informazioni rischi sicurezza' => [
                'max_pages' => 1,
                'fields' => [],
            ],
            'Ricevuta con segno di taglio' => [
                'max_pages' => 1,
                'fields' => [
                    ['label' => 'Note', 'format' => 'testo', 'max_characters_key' => 'note'],
                ],
            ],
            'Risguardo' => [
                'max_pages' => 1,
                'fields' => [
                    ['label' => 'Note', 'format' => 'testo', 'max_characters_key' => 'note'],
                ],
            ],
        ];
    }

    private function templateTypeDefinitions(): array
    {
        $smartyBlocks = [
            ['page_type' => 'Dati scuola', 'max_pages' => 1],
            ['page_type' => 'Orari uffici', 'max_pages' => 1],
            ['page_type' => 'Dati studente', 'max_pages' => 1],
            ['page_type' => 'Calendario scolastico', 'max_pages' => 1],
            ['page_type' => 'PTOF', 'max_pages' => 10],
            ['page_type' => 'Regolamenti (Istituto, Disciplina, Cellulari...)', 'max_pages' => 10],
            ['page_type' => 'Patto educativo (Primaria, Secondaria...)', 'max_pages' => 10],
            ['page_type' => 'Tabella orari provvisorio x 2', 'max_pages' => 2],
            ['page_type' => 'Tabella orario definitivo x1', 'max_pages' => 2],
            ['page_type' => 'Tabella Colloqui', 'max_pages' => 1],
            ['page_type' => 'Tabella Libri di testo', 'max_pages' => 1],
            ['page_type' => 'Tabella Elenco materiale', 'max_pages' => 1],
            ['page_type' => 'Tabella Compagni di classe', 'max_pages' => 5],
            ['page_type' => 'Tabella Disciplina x1', 'max_pages' => 5],
            ['page_type' => 'Tabella Disciplina x2', 'max_pages' => 5],
            ['page_type' => 'Giochi', 'max_pages' => 1],
            ['page_type' => 'Appunti', 'max_pages' => 30],
            ['page_type' => 'Plessi', 'max_pages' => 6],
            ['page_type' => 'Autorizzazioni / deleghe con segno di taglio x 1', 'max_pages' => 10],
            ['page_type' => 'Autorizzazioni / deleghe con segno di taglio x 2', 'max_pages' => 10],
            ['page_type' => 'Pagina bianca - retro autorizzazioni / deleghe con taglio', 'max_pages' => 10],
            ['page_type' => 'Gallery immagini', 'max_pages' => 15],
            ['page_type' => 'Pagina libera componibile', 'max_pages' => 15],
        ];

        return [
            'Smarty' => [
                'is_custom_finale' => false,
                'size' => TemplateType::SIZE_L,
                'blocks' => $smartyBlocks,
            ],
            'In Linea' => [
                'is_custom_finale' => false,
                'size' => TemplateType::SIZE_L,
                'blocks' => $smartyBlocks,
            ],
            'Ottavino' => [
                'is_custom_finale' => false,
                'size' => TemplateType::SIZE_L,
                'blocks' => [
                    ['page_type' => 'Dati studente', 'max_pages' => 1],
                    ['page_type' => 'Tabella orari provvisorio x 2', 'max_pages' => 2],
                    ['page_type' => 'Tabella orario definitivo x1', 'max_pages' => 2],
                    ['page_type' => 'Tabella Colloqui', 'max_pages' => 1],
                    ['page_type' => 'Tabella Elenco materiale', 'max_pages' => 1],
                    ['page_type' => 'Tabella Compagni di classe', 'max_pages' => 5],
                ],
            ],
            'Diario 2.0' => [
                'is_custom_finale' => true,
                'size' => TemplateType::SIZE_M,
                'blocks' => [
                    ['page_type' => 'Dati scuola', 'max_pages' => 1],
                    ['page_type' => 'Presentazione Scuola', 'max_pages' => 1],
                    ['page_type' => 'Orari uffici', 'max_pages' => 1],
                    ['page_type' => 'Dati studente', 'max_pages' => 1],
                    ['page_type' => 'Calendario scolastico', 'max_pages' => 1],
                    ['page_type' => 'PTOF', 'max_pages' => 10],
                    ['page_type' => 'Regolamenti (Istituto, Disciplina, Cellulari...)', 'max_pages' => 10],
                    ['page_type' => 'Patto educativo (Primaria, Secondaria...)', 'max_pages' => 10],
                    ['page_type' => 'Tabella orari provvisorio x 2', 'max_pages' => 2],
                    ['page_type' => 'Tabella orario definitivo x1', 'max_pages' => 2],
                    ['page_type' => 'Tabella Colloqui', 'max_pages' => 1],
                    ['page_type' => 'Tabella Libri di testo', 'max_pages' => 1],
                    ['page_type' => 'Tabella Elenco materiale', 'max_pages' => 1],
                    ['page_type' => 'Tabella Interrogazioni', 'max_pages' => 1],
                    ['page_type' => 'Tabella Compagni di classe', 'max_pages' => 5],
                    ['page_type' => 'Tabella Disciplina x1', 'max_pages' => 5],
                    ['page_type' => 'Tabella Disciplina x2', 'max_pages' => 5],
                    ['page_type' => 'Comunicazioni Scuola Famiglia', 'max_pages' => 5],
                    ['page_type' => 'Note', 'max_pages' => 30],
                    ['page_type' => 'Plessi', 'max_pages' => 4],
                    ['page_type' => 'Autorizzazioni / deleghe con segno di taglio x 1', 'max_pages' => 10],
                    ['page_type' => 'Autorizzazioni / deleghe con segno di taglio x 2', 'max_pages' => 10],
                    ['page_type' => 'Pagina bianca - retro autorizzazioni / deleghe con taglio', 'max_pages' => 10],
                    ['page_type' => 'Gallery immagini', 'max_pages' => 15],
                    ['page_type' => 'Pagina libera componibile', 'max_pages' => 15],
                    ["page_type" => "Autorizzazione visite guidate tutto l'anno con segno di taglio x 1", 'max_pages' => 1],
                    ['page_type' => 'Benestare uscita didattica e viaggio istr. con segno di taglio x 2', 'max_pages' => 15],
                    ['page_type' => 'Informazioni rischi sicurezza', 'max_pages' => 1],
                    ['page_type' => 'Ricevuta con segno di taglio', 'max_pages' => 1],
                    ['page_type' => 'Risguardo', 'max_pages' => 1],
                    ['page_type' => 'Risguardo', 'max_pages' => 1],
                    ['page_type' => 'Regolamenti (Istituto, Disciplina, Cellulari...)', 'max_pages' => 5],
                    ['page_type' => 'Tabella Disciplina x1', 'max_pages' => 5],
                    ['page_type' => 'Tabella Disciplina x2', 'max_pages' => 5],
                    ['page_type' => 'Autorizzazioni / deleghe con segno di taglio x 1', 'max_pages' => 5],
                    ['page_type' => 'Autorizzazioni / deleghe con segno di taglio x 2', 'max_pages' => 5],
                    ['page_type' => 'Pagina bianca - retro autorizzazioni / deleghe con taglio', 'max_pages' => 10],
                ],
            ],
            'Il Mio Diario' => [
                'is_custom_finale' => true,
                'size' => TemplateType::SIZE_L,
                'blocks' => [
                    ['page_type' => 'Dati scuola', 'max_pages' => 1],
                    ['page_type' => 'Orari uffici', 'max_pages' => 1],
                    ['page_type' => 'Dati studente', 'max_pages' => 1],
                    ['page_type' => 'Calendario scolastico', 'max_pages' => 1],
                    ['page_type' => 'PTOF', 'max_pages' => 10],
                    ['page_type' => 'Regolamenti (Istituto, Disciplina, Cellulari...)', 'max_pages' => 10],
                    ['page_type' => 'Patto educativo (Primaria, Secondaria...)', 'max_pages' => 10],
                    ['page_type' => 'Tabella orari provvisorio x 2', 'max_pages' => 2],
                    ['page_type' => 'Tabella orario definitivo x1', 'max_pages' => 2],
                    ['page_type' => 'Tabella Colloqui', 'max_pages' => 1],
                    ['page_type' => 'Tabella Libri di testo', 'max_pages' => 1],
                    ['page_type' => 'Tabella Elenco materiale', 'max_pages' => 1],
                    ['page_type' => 'Tabella Compagni di classe', 'max_pages' => 5],
                    ['page_type' => 'Tabella Disciplina x1', 'max_pages' => 5],
                    ['page_type' => 'Tabella Disciplina x2', 'max_pages' => 5],
                    ['page_type' => 'Giochi', 'max_pages' => 30],
                    ['page_type' => 'Appunti', 'max_pages' => 30],
                    ['page_type' => 'Plessi', 'max_pages' => 6],
                    ['page_type' => 'Autorizzazioni / deleghe con segno di taglio x 1', 'max_pages' => 10],
                    ['page_type' => 'Autorizzazioni / deleghe con segno di taglio x 2', 'max_pages' => 10],
                    ['page_type' => 'Pagina bianca - retro autorizzazioni / deleghe con taglio', 'max_pages' => 10],
                    ['page_type' => 'Gallery immagini', 'max_pages' => 15],
                    ['page_type' => 'Pagina libera componibile', 'max_pages' => 15],
                    ["page_type" => "Autorizzazione visite guidate tutto l'anno con segno di taglio x 1", 'max_pages' => 1],
                    ['page_type' => 'Benestare uscita didattica e viaggio istr. con segno di taglio x 2', 'max_pages' => 15],
                    ['page_type' => 'Informazioni rischi sicurezza', 'max_pages' => 1],
                    ['page_type' => 'Ricevuta con segno di taglio', 'max_pages' => 1],
                    ['page_type' => 'Regolamenti (Istituto, Disciplina, Cellulari...)', 'max_pages' => 5],
                    ['page_type' => 'Tabella Disciplina x1', 'max_pages' => 5],
                    ['page_type' => 'Tabella Disciplina x2', 'max_pages' => 5],
                ],
            ],
        ];
    }
}
