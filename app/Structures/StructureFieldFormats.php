<?php

declare(strict_types=1);

namespace App\Structures;

final class StructureFieldFormats
{
    public const FORMAT_TEXT = 'testo';
    public const FORMAT_IMAGE = 'immagine';
    public const FORMAT_EMPTY_LINE = 'riga vuota';
    public const FORMAT_EMPTY_TABLE = 'tabella vuota';
    public const FORMAT_TINYMCE = 'tinymce';

    public static function options(): array
    {
        return [
            self::FORMAT_TEXT => 'Testo',
            self::FORMAT_IMAGE => 'Immagine',
            self::FORMAT_EMPTY_LINE => 'Riga vuota',
            self::FORMAT_EMPTY_TABLE => 'Tabella vuota',
            self::FORMAT_TINYMCE => 'TinyMCE',
        ];
    }
}
