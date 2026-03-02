<?php

declare(strict_types=1);

namespace App\Structures;

use App\Filament\Forms\Components\TinyMceEditor;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;

final class StructureFormFactory
{
    /**
     * @return array<int, mixed>
     */
    public static function makeValueComponents(
        mixed $structure,
        string $statePrefix = 'structure_values',
        ?array $constraints = null,
        ?array $settings = null,
        bool $valuesEditable = true,
    ): array {
        if (! is_array($structure)) {
            return [
                self::makeReadOnlyMessageComponent(
                    name: $statePrefix . '.empty_message',
                    message: 'Nessuna Struttura disponibile per la compilazione.',
                    label: 'Struttura',
                ),
            ];
        }

        $maxUploadMb = self::settingsInt($settings, 'MAX_UPLOAD_MB', 20);
        $imageMinDpi = self::settingsInt($settings, 'IMAGE_MIN_DPI', 300);
        $autosaveSeconds = self::settingsInt($settings, 'AUTO_SAVE_SECONDS', 15);

        $allowedFormats = self::settingsArray($settings, 'IMAGE_ALLOWED_FORMATS', ['jpg', 'jpeg', 'png', 'tif', 'tiff']);

        $imageAcceptedMimes = self::imageMimesFromFormats($allowedFormats);
        $fileAcceptedMimes = array_values(array_unique(array_merge(
            ['application/pdf'],
            $imageAcceptedMimes,
        )));

        $debounceMs = max(250, $autosaveSeconds * 1000);

        $components = [];

        foreach ($structure as $b => $entry) {
            if (! is_int($b) || ! is_array($entry)) {
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

            $components[] = ViewField::make($statePrefix . '.entry_title.' . $b)
                ->view('filament.structures.structure-entry-title')
                ->dehydrated(false)
                ->viewData([
                    'title' => $title,
                ]);

            foreach ($fields as $f => $field) {
                if (! is_int($f) || ! is_array($field)) {
                    continue;
                }

                $formatRaw = trim((string) ($field['format'] ?? StructureFieldFormats::FORMAT_TEXT));
                $format = mb_strtolower($formatRaw);

                $label = self::resolveFieldLabel($field, $format, $b, $f);
                $valuePath = $statePrefix . '.' . $b . '.' . $f;

                $currentValue = $field['value'] ?? null;

                if ($format === mb_strtolower(StructureFieldFormats::FORMAT_EMPTY_LINE)) {
                    $helperText = self::withReadOnlySuffix('Campo di separazione non compilabile.', $valuesEditable);

                    $components[] = TextInput::make($statePrefix . '.empty_line.' . $b . '.' . $f)
                        ->label($label)
                        ->default('Riga vuota')
                        ->disabled()
                        ->dehydrated(false)
                        ->columnSpanFull()
                        ->helperText($helperText);

                    continue;
                }

                if ($format === mb_strtolower(StructureFieldFormats::FORMAT_EMPTY_TABLE)) {
                    $tableSize = trim((string) ($field['table_size'] ?? ''));

                    $dims = self::parseTableSize($tableSize);
                    $rows = (int) ($dims['rows'] ?? 0);
                    $cols = (int) ($dims['cols'] ?? 0);

                    if ($rows <= 0) {
                        $rows = 3;
                    }

                    if ($cols <= 0) {
                        $cols = 3;
                    }

                    $rows = min($rows, 20);
                    $cols = min($cols, 12);

                    $helper = 'Campo non compilabile.';
                    if ($tableSize !== '') {
                        $helper .= ' Dimensione ' . $tableSize . '.';
                    }

                    $helperText = self::withReadOnlySuffix($helper, $valuesEditable);

                    $components[] = ViewField::make($statePrefix . '.empty_table.' . $b . '.' . $f)
                        ->label($label)
                        ->view('filament.structures.structure-empty-table')
                        ->dehydrated(false)
                        ->columnSpanFull()
                        ->viewData([
                            'rows' => $rows,
                            'cols' => $cols,
                            'helperText' => $helperText,
                        ]);

                    continue;
                }

                if ($format === 'messaggio' || $format === 'message') {
                    $message = null;

                    if (is_string($currentValue)) {
                        $message = trim($currentValue);
                    }

                    if ($message === null || $message === '') {
                        $message = $label;
                    }

                    $components[] = ViewField::make($statePrefix . '.message.' . $b . '.' . $f)
                        ->label($label)
                        ->view('filament.structures.structure-field-message')
                        ->dehydrated(false)
                        ->columnSpanFull()
                        ->viewData([
                            'message' => $message,
                        ]);

                    continue;
                }

                if (in_array($format, ['immagine', 'file'], true) || $format === mb_strtolower(StructureFieldFormats::FORMAT_IMAGE)) {
                    $maxSizeMb = self::resolveMaxSizeMb($field, $maxUploadMb);

                    $helperTextParts = [];

                    if ($allowedFormats !== []) {
                        $helperTextParts[] = 'Formati ' . self::formatsToLabel($allowedFormats) . '.';
                    }

                    if ($maxSizeMb > 0) {
                        $helperTextParts[] = 'Max ' . $maxSizeMb . ' MB.';
                    }

                    if ($format === 'immagine' || $format === mb_strtolower(StructureFieldFormats::FORMAT_IMAGE)) {
                        if ($imageMinDpi > 0) {
                            $helperTextParts[] = 'Min ' . $imageMinDpi . ' DPI.';
                        }
                    }

                    $helperText = self::withReadOnlySuffix(implode(' ', $helperTextParts), $valuesEditable);

                    $upload = FileUpload::make($valuePath)
                        ->label($label)
                        ->nullable()
                        ->disk('public')
                        ->directory('structure-uploads')
                        ->visibility('public')
                        ->disabled(! $valuesEditable)
                        ->dehydrated($valuesEditable)
                        ->live(debounce: $debounceMs)
                        ->columnSpanFull();

                    if ($helperText !== '') {
                        $upload->helperText($helperText);
                    }

                    if ($fileAcceptedMimes !== []) {
                        $upload->acceptedFileTypes($fileAcceptedMimes);
                    }

                    if ($maxSizeMb > 0) {
                        $upload->maxSize($maxSizeMb * 1024);
                    }

                    $components[] = $upload;

                    continue;
                }

                if ($format === mb_strtolower(StructureFieldFormats::FORMAT_TINYMCE)) {
                    $maxCharacters = self::resolveMaxCharacters($field, $constraints);

                    $helperText = null;
                    if (is_int($maxCharacters) && $maxCharacters > 0) {
                        $helperText = 'Max ' . $maxCharacters . ' caratteri.';
                    }

                    $helperText = self::withReadOnlySuffix((string) $helperText, $valuesEditable);

                    $editor = TinyMceEditor::make($valuePath)
                        ->label($label)
                        ->nullable()
                        ->default(is_string($currentValue) ? $currentValue : null)
                        ->live(debounce: $debounceMs)
                        ->disabled(! $valuesEditable)
                        ->dehydrated($valuesEditable)
                        ->columnSpanFull();

                    if ($helperText !== '') {
                        $editor->helperText($helperText);
                    }

                    $components[] = $editor;

                    continue;
                }

                $maxCharacters = self::resolveMaxCharacters($field, $constraints);

                $helperText = null;
                if (is_int($maxCharacters) && $maxCharacters > 0) {
                    $helperText = 'Max ' . $maxCharacters . ' caratteri.';
                }

                $helperText = self::withReadOnlySuffix((string) $helperText, $valuesEditable);

                if (is_int($maxCharacters) && $maxCharacters > 200) {
                    $textarea = Textarea::make($valuePath)
                        ->label($label)
                        ->nullable()
                        ->default(is_string($currentValue) ? $currentValue : null)
                        ->disabled(! $valuesEditable)
                        ->dehydrated($valuesEditable)
                        ->live(debounce: $debounceMs)
                        ->rows(4)
                        ->columnSpanFull();

                    if ($helperText !== '') {
                        $textarea->helperText($helperText);
                    }

                    if ($maxCharacters > 0) {
                        $textarea->maxLength($maxCharacters);
                    }

                    $components[] = $textarea;
                } else {
                    $input = TextInput::make($valuePath)
                        ->label($label)
                        ->nullable()
                        ->default(is_string($currentValue) ? $currentValue : null)
                        ->disabled(! $valuesEditable)
                        ->dehydrated($valuesEditable)
                        ->live(debounce: $debounceMs)
                        ->columnSpanFull();

                    if ($helperText !== '') {
                        $input->helperText($helperText);
                    }

                    if (is_int($maxCharacters) && $maxCharacters > 0) {
                        $input->maxLength($maxCharacters);
                    }

                    $components[] = $input;
                }
            }
        }

        if ($components !== []) {
            return $components;
        }

        return [
            self::makeReadOnlyMessageComponent(
                name: $statePrefix . '.empty_message',
                message: 'Nessuna Struttura disponibile per la compilazione.',
                label: 'Struttura',
            ),
        ];
    }

    private static function resolveMaxCharacters(array $field, ?array $constraints): ?int
    {
        $maxCharacters = $field['max_characters'] ?? null;

        if (is_numeric($maxCharacters)) {
            $v = (int) $maxCharacters;

            return $v > 0 ? $v : null;
        }

        $key = $field['max_characters_key'] ?? null;
        $key = is_string($key) ? trim($key) : null;

        if (! is_array($constraints) || $key === null || $key === '') {
            return null;
        }

        $value = $constraints[$key] ?? null;

        if (! is_numeric($value)) {
            return null;
        }

        $v = (int) $value;

        return $v > 0 ? $v : null;
    }

    private static function resolveMaxSizeMb(array $field, int $fallbackMaxUploadMb): int
    {
        $fieldMaxSizeMb = $field['max_size'] ?? null;
        $fieldMaxSizeMb = is_numeric($fieldMaxSizeMb) ? max(0, (int) $fieldMaxSizeMb) : 0;

        $fallbackMaxUploadMb = max(0, $fallbackMaxUploadMb);

        if ($fieldMaxSizeMb > 0) {
            return $fieldMaxSizeMb;
        }

        return $fallbackMaxUploadMb;
    }

    /**
     * @return array{rows?: int, cols?: int}
     */
    private static function parseTableSize(string $tableSize): array
    {
        $tableSize = trim($tableSize);

        if ($tableSize === '') {
            return [];
        }

        if (! preg_match('/^(\d+)\s*[xX]\s*(\d+)$/', $tableSize, $m)) {
            return [];
        }

        $rows = (int) ($m[1] ?? 0);
        $cols = (int) ($m[2] ?? 0);

        if ($rows <= 0 || $cols <= 0) {
            return [];
        }

        return [
            'rows' => $rows,
            'cols' => $cols,
        ];
    }

    private static function formatsToLabel(array $formats): string
    {
        $out = [];

        foreach ($formats as $f) {
            $f = strtoupper(trim((string) $f));

            if ($f === '') {
                continue;
            }

            $out[] = $f;
        }

        $out = array_values(array_unique($out));

        return implode(', ', $out);
    }

    private static function imageMimesFromFormats(array $formats): array
    {
        $map = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'tif' => 'image/tiff',
            'tiff' => 'image/tiff',
            'webp' => 'image/webp',
        ];

        $out = [];

        foreach ($formats as $fmt) {
            $fmt = mb_strtolower(trim((string) $fmt));

            if ($fmt === '') {
                continue;
            }

            if (isset($map[$fmt])) {
                $out[] = $map[$fmt];
            }
        }

        return array_values(array_unique($out));
    }

    private static function makeReadOnlyMessageComponent(string $name, string $message, string $label): TextInput
    {
        return TextInput::make($name)
            ->label($label)
            ->default($message)
            ->disabled()
            ->dehydrated(false)
            ->columnSpanFull();
    }

    private static function resolveFieldLabel(array $field, string $format, int $blockIndex, int $fieldIndex): string
    {
        $raw = trim((string) ($field['label'] ?? ''));

        if ($raw !== '') {
            return $raw;
        }

        $raw = trim((string) ($field['name'] ?? ''));

        if ($raw !== '') {
            return $raw;
        }

        $fallback = match ($format) {
            mb_strtolower(StructureFieldFormats::FORMAT_IMAGE) => 'Immagine',
            mb_strtolower(StructureFieldFormats::FORMAT_TINYMCE) => 'Testo editor',
            mb_strtolower(StructureFieldFormats::FORMAT_EMPTY_LINE) => 'Riga vuota',
            mb_strtolower(StructureFieldFormats::FORMAT_EMPTY_TABLE) => 'Tabella vuota',
            'file' => 'File',
            'messaggio', 'message' => 'Messaggio',
            default => 'Testo',
        };

        $n = $fieldIndex + 1;

        return $fallback . ' ' . $n;
    }

    private static function withReadOnlySuffix(string $text, bool $valuesEditable): string
    {
        $text = trim($text);

        if ($valuesEditable) {
            return $text;
        }

        if ($text === '') {
            return 'Sola lettura.';
        }

        return $text . ' Sola lettura.';
    }

    private static function settingsInt(?array $settings, string $key, int $default): int
    {
        if (! is_array($settings)) {
            return $default;
        }

        $value = $settings[$key] ?? null;

        if (! is_numeric($value)) {
            return $default;
        }

        return (int) $value;
    }

    private static function settingsArray(?array $settings, string $key, array $default): array
    {
        if (! is_array($settings)) {
            return $default;
        }

        $value = $settings[$key] ?? null;

        if (! is_array($value)) {
            return $default;
        }

        return $value;
    }
}
