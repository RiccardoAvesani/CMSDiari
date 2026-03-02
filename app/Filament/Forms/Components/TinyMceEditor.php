<?php

declare(strict_types=1);

namespace App\Filament\Forms\Components;

use Closure;
use Filament\Forms\Components\Field;

class TinyMceEditor extends Field
{
    protected string $view = 'filament.forms.components.tiny-mce-editor';

    protected string|Closure|null $scriptSrc = null;

    protected string|Closure|null $baseUrl = null;

    /**
     * @var array<string, mixed>|Closure
     */
    protected array|Closure $editorConfig = [];

    public function scriptSrc(string|Closure|null $scriptSrc): static
    {
        $this->scriptSrc = $scriptSrc;

        return $this;
    }

    public function baseUrl(string|Closure|null $baseUrl): static
    {
        $this->baseUrl = $baseUrl;

        return $this;
    }

    /**
     * @param  array<string, mixed>|Closure  $config
     */
    public function editorConfig(array|Closure $config): static
    {
        $this->editorConfig = $config;

        return $this;
    }

    public function getScriptSrc(): string
    {
        $src = $this->evaluate($this->scriptSrc);

        if (is_string($src) && trim($src) !== '') {
            return trim($src);
        }

        return '/vendor/tinymce/tinymce.min.js';
    }

    public function getBaseUrl(): ?string
    {
        $base = $this->evaluate($this->baseUrl);

        if (is_string($base) && trim($base) !== '') {
            return rtrim(trim($base), '/');
        }

        $src = $this->getScriptSrc();

        if (preg_match('/^https?:\/\//i', $src)) {
            return null;
        }

        $dir = rtrim(str_replace('\\', '/', dirname($src)), '/');

        return $dir !== '.' ? $dir : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function getEditorConfig(): array
    {
        $cfg = $this->evaluate($this->editorConfig);

        if (! is_array($cfg)) {
            $cfg = [];
        }

        $defaults = [
            'license_key' => 'gpl',
            'menubar' => false,
            'branding' => false,
            'height' => 320,
            'plugins' => 'lists link code table autoresize',
            'toolbar' => 'undo redo | bold italic underline | alignleft aligncenter alignright | bullist numlist | link table | removeformat | code',
            'valid_elements' => '*[*]',
        ];

        $cfg = array_replace($defaults, $cfg);

        $baseUrl = $this->getBaseUrl();

        if (is_string($baseUrl) && $baseUrl !== '') {
            $cfg['base_url'] = $cfg['base_url'] ?? $baseUrl;
            $cfg['suffix'] = $cfg['suffix'] ?? '.min';
        }

        return $cfg;
    }
}
