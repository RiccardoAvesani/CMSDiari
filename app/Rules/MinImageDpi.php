<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Throwable;

final class MinImageDpi implements ValidationRule
{
    public function __construct(private readonly int $minDpi) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->minDpi <= 0) {
            return;
        }

        if (is_array($value)) {
            foreach ($value as $v) {
                $this->validate($attribute, $v, $fail);
            }

            return;
        }

        if (! $value instanceof TemporaryUploadedFile) {
            return;
        }

        $path = $value->getRealPath();

        if (! is_string($path) || $path === '' || ! is_file($path)) {
            return;
        }

        $extension = strtolower((string) $value->getClientOriginalExtension());

        $dpi = $this->extractDpi($path, $extension);

        if ($dpi === null) {
            return;
        }

        if ($dpi < $this->minDpi) {
            $fail("L'immagine deve avere una risoluzione di almeno {$this->minDpi} DPI (rilevata: {$dpi} DPI).");
        }
    }

    private function extractDpi(string $path, string $extension): ?int
    {
        $dpi = null;

        if (in_array($extension, ['jpg', 'jpeg', 'tif', 'tiff'], true)) {
            $dpi = $this->extractExifDpi($path);
        }

        if ($dpi === null && $extension === 'png') {
            $dpi = $this->extractGdDpi($path);
        }

        return is_int($dpi) && $dpi > 0 ? $dpi : null;
    }

    private function extractExifDpi(string $path): ?int
    {
        if (! function_exists('exif_read_data')) {
            return null;
        }

        try {
            $exif = @exif_read_data($path);
        } catch (Throwable) {
            return null;
        }

        if (! is_array($exif)) {
            return null;
        }

        $xResolution = $exif['XResolution'] ?? null;
        $unit = $exif['ResolutionUnit'] ?? 2;

        $x = $this->toFloat($xResolution);

        if ($x === null || $x <= 0) {
            return null;
        }

        // 2 = inches, 3 = centimeters (EXIF)
        if ((int) $unit === 3) {
            $x = $x * 2.54;
        }

        return (int) round($x);
    }

    private function extractGdDpi(string $path): ?int
    {
        if (! function_exists('imagecreatefrompng') || ! function_exists('imageresolution')) {
            return null;
        }

        $img = @imagecreatefrompng($path);

        if ($img === false) {
            return null;
        }

        $res = @imageresolution($img);

        imagedestroy($img);

        if (! is_array($res) || ! isset($res[0])) {
            return null;
        }

        $x = (int) $res[0];

        return $x > 0 ? $x : null;
    }

    private function toFloat(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if (str_contains($value, '/')) {
            [$n, $d] = array_pad(explode('/', $value, 2), 2, null);

            if (is_numeric($n) && is_numeric($d) && (float) $d !== 0.0) {
                return (float) $n / (float) $d;
            }
        }

        return is_numeric($value) ? (float) $value : null;
    }
}
