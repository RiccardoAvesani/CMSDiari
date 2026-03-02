<?php

declare(strict_types=1);

use App\Structures\StructurePresenter;
use Closure;
use Illuminate\Support\Facades\Auth;
use Throwable;

$livewire = $this ?? null;
$record = $record ?? null;

$evaluateMaybeClosure = static function (mixed $value) use ($record, $livewire): mixed {
    if (! $value instanceof Closure) {
        return $value;
    }

    try {
        return $value($record);
    } catch (Throwable) {
        try {
            return $value($livewire);
        } catch (Throwable) {
            try {
                return $value();
            } catch (Throwable) {
                return null;
            }
        }
    }
};

$structureRaw = $evaluateMaybeClosure($structure ?? null);

if ($structureRaw === null && is_object($record) && isset($record->structure)) {
    $structureRaw = $record->structure;
}

$constraintsRaw = $evaluateMaybeClosure($constraints ?? null);

if ($constraintsRaw === null && is_object($record) && isset($record->constraints)) {
    $constraintsRaw = $record->constraints;
}

$constraintsArray = is_array($constraintsRaw) ? $constraintsRaw : null;

$hideValues = (bool) ($evaluateMaybeClosure($hideValues ?? false) ?? false);
$editableValues = (bool) ($evaluateMaybeClosure($editableValues ?? false) ?? false);
$disabled = ! $editableValues;

$user = Auth::user();
$role = (string) ($user?->role ?? '');
$isExternal = str_starts_with($role, 'external');

$presented = StructurePresenter::present($structureRaw, $constraintsArray);

$entries = $presented['entries'] ?? [];
if (! is_array($entries)) {
    $entries = [];
}

$prettyJson = $presented['pretty_json'] ?? null;
$prettyJson = is_string($prettyJson) ? $prettyJson : null;

$formatIs = static fn(?string $format, string $expected): bool => mb_strtolower(trim((string) $format)) === mb_strtolower($expected);

$buildHelperText = static function (array $field): string {
    $parts = [];

    $maxCharacters = $field['max_characters'] ?? null;
    if (is_numeric($maxCharacters)) {
        $maxCharacters = (int) $maxCharacters;
        if ($maxCharacters > 0) {
            $parts[] = 'Max ' . $maxCharacters . ' caratteri.';
        }
    }

    $maxSize = $field['max_size'] ?? null;
    if (is_numeric($maxSize)) {
        $maxSize = (int) $maxSize;
        if ($maxSize > 0) {
            $parts[] = 'Max ' . $maxSize . ' MB.';
        }
    }

    $tableSize = $field['table_size'] ?? null;
    if (is_string($tableSize)) {
        $tableSize = trim($tableSize);
        if ($tableSize !== '') {
            $parts[] = 'Dimensione ' . $tableSize . '.';
        }
    }

    return implode(' ', $parts);
};
?>

<div x-data="{ mode: 'html' }" class="space-y-4">
    <div class="flex items-center justify-between gap-3">
        <div class="text-sm font-medium">Struttura</div>

        <?php if (! $isExternal && is_string($prettyJson) && trim($prettyJson) !== ''): ?>
            <div>
                <button
                    type="button"
                    class="rounded-md bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-800 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-100 dark:hover:bg-gray-700"
                    x-on:click="mode = (mode === 'json') ? 'html' : 'json'">
                    <span x-show="mode === 'html'">Mostra JSON</span>
                    <span x-show="mode === 'json'">Mostra HTML</span>
                </button>
            </div>
        <?php endif; ?>
    </div>

    <?php if (! $isExternal): ?>
        <div x-show="mode === 'json'" class="space-y-1">
            <div class="text-xs font-medium text-gray-700 dark:text-gray-200">JSON</div>

            <textarea
                class="w-full rounded-md border-gray-300 bg-gray-50 font-mono text-xs shadow-sm dark:border-gray-700 dark:bg-gray-950"
                rows="18"
                disabled><?php echo e($prettyJson ?? ''); ?></textarea>

            <div class="text-11px text-gray-500">
                Visualizzazione sola lettura.
            </div>
        </div>
    <?php endif; ?>

    <div x-show="mode === 'html'" class="space-y-6">
        <?php if (count($entries) === 0): ?>
            <div class="rounded-lg border border-gray-200 bg-white p-3 text-xs text-gray-500 dark:border-gray-800 dark:bg-gray-900">
                Nessuna struttura disponibile.
            </div>
        <?php else: ?>
            <?php foreach ($entries as $entry): ?>
                <?php
                if (! is_array($entry)) {
                    continue;
                }

                $title = $entry['title'] ?? null;
                $title = is_string($title) ? trim($title) : '';
                if ($title === '') {
                    $title = 'Struttura';
                }

                $fields = $entry['fields'] ?? [];
                $fields = is_array($fields) ? $fields : [];
                ?>

                <div class="space-y-4">
                    <div class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                        <?php echo e($title); ?>
                    </div>

                    <?php if (count($fields) === 0): ?>
                        <div class="text-xs text-gray-500">Nessun campo.</div>
                    <?php else: ?>
                        <?php foreach ($fields as $field): ?>
                            <?php
                            if (! is_array($field)) {
                                continue;
                            }

                            $label = $field['label'] ?? null;
                            $label = is_string($label) ? trim($label) : '';
                            if ($label === '') {
                                $label = 'Campo';
                            }

                            $formatRaw = $field['format'] ?? 'testo';
                            $format = mb_strtolower(trim((string) $formatRaw));

                            $helperText = $buildHelperText($field);

                            $maxCharacters = $field['max_characters'] ?? null;
                            $maxCharacters = is_numeric($maxCharacters) ? (int) $maxCharacters : null;

                            $value = $field['value'] ?? null;

                            if ($hideValues) {
                                $value = null;
                            } elseif (is_array($value) || is_object($value)) {
                                $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                            } elseif ($value === null) {
                                $value = '';
                            } else {
                                $value = (string) $value;
                            }

                            $value = is_string($value) ? $value : '';

                            $useTextarea = is_int($maxCharacters) && $maxCharacters > 200;

                            $tableDimensions = $field['table_dimensions'] ?? null;
                            $rows = 0;
                            $cols = 0;
                            if (is_array($tableDimensions)) {
                                $rows = (int) ($tableDimensions['rows'] ?? 0);
                                $cols = (int) ($tableDimensions['cols'] ?? 0);
                            }

                            if ($rows <= 0) {
                                $rows = 3;
                            }
                            if ($cols <= 0) {
                                $cols = 3;
                            }

                            $rows = min(max(1, $rows), 16);
                            $cols = min(max(1, $cols), 24);
                            ?>

                            <div class="space-y-1">
                                <div class="text-xs font-medium text-gray-700 dark:text-gray-200">
                                    <?php echo e($label); ?>
                                </div>

                                <?php if ($formatIs($format, 'riga vuota')): ?>
                                    <input
                                        type="text"
                                        class="w-full rounded-md border-gray-300 bg-gray-50 text-sm shadow-sm dark:border-gray-700 dark:bg-gray-950"
                                        value="Riga vuota"
                                        disabled />
                                <?php elseif ($formatIs($format, 'tabella vuota')): ?>
                                    <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                                        <div class="border-b border-gray-200 px-3 py-2 text-xs text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                            Tabella vuota
                                        </div>
                                        <div class="overflow-auto p-2">
                                            <table class="border-collapse">
                                                <tbody>
                                                    <?php for ($r = 0; $r < $rows; $r++): ?>
                                                        <tr>
                                                            <?php for ($c = 0; $c < $cols; $c++): ?>
                                                                <td class="h-5 w-8 border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800"></td>
                                                            <?php endfor; ?>
                                                        </tr>
                                                    <?php endfor; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                <?php elseif ($formatIs($format, 'messaggio') || $formatIs($format, 'message')): ?>
                                    <?php
                                    $message = trim($value) !== '' ? $value : $label;
                                    ?>
                                    <div class="rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-600 shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                        <?php echo e($message); ?>
                                    </div>
                                <?php elseif (in_array($format, ['immagine', 'file'], true)): ?>
                                    <?php
                                    $display = trim($value) !== '' ? $value : 'Nessun file caricato.';
                                    ?>
                                    <input
                                        type="text"
                                        class="w-full rounded-md border-gray-300 bg-gray-50 text-sm shadow-sm dark:border-gray-700 dark:bg-gray-950"
                                        value="<?php echo e($display); ?>"
                                        disabled />
                                <?php else: ?>
                                    <?php if ($useTextarea): ?>
                                        <textarea
                                            class="w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-700 dark:bg-gray-950"
                                            rows="4"
                                            <?php echo $disabled ? 'disabled' : ''; ?>><?php echo e($value); ?></textarea>
                                    <?php else: ?>
                                        <input
                                            type="text"
                                            class="w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-700 dark:bg-gray-950"
                                            value="<?php echo e($value); ?>"
                                            <?php echo $disabled ? 'disabled' : ''; ?> />
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php if (is_string($helperText) && trim($helperText) !== ''): ?>
                                    <div class="text-11px text-gray-500">
                                        <?php echo e($helperText); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>