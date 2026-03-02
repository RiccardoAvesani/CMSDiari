<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Closure;
use Throwable;

$livewire = $this ?? null;

$evaluateMaybeClosure = static function (mixed $value) use ($livewire): mixed {
    if (! $value instanceof Closure) {
        return $value;
    }

    try {
        return $value($livewire);
    } catch (Throwable) {
        try {
            return $value();
        } catch (Throwable) {
            return null;
        }
    }
};

$label = $evaluateMaybeClosure($label ?? 'Struttura');
$toggleMethod = $evaluateMaybeClosure($toggleMethod ?? null);
$mode = $evaluateMaybeClosure($mode ?? 'html');
$error = $evaluateMaybeClosure($error ?? null);
$canEdit = $evaluateMaybeClosure($canEdit ?? true);

$label = is_scalar($label) ? (string) $label : 'Struttura';
$toggleMethod = is_scalar($toggleMethod) ? (string) $toggleMethod : null;
$mode = is_scalar($mode) ? (string) $mode : 'html';
$mode = in_array($mode, ['html', 'json'], true) ? $mode : 'html';
$error = is_scalar($error) ? (string) $error : null;
$error = $error !== null ? trim($error) : null;
$canEdit = (bool) $canEdit;
/** @var User|null $user */
$user = Auth::user();
$role = (string) ($user?->role ?? '');
$isExternal = str_starts_with($role, 'external');

if ($isExternal) {
    $toggleMethod = null;
}
?>

<div class="flex flex-col gap-2">
    <div class="flex items-center justify-between gap-3">
        <div class="text-sm font-medium">{{ $label }}</div>

        @if (! empty($toggleMethod))
        <div class="flex items-center gap-2">
            @if ($mode === 'html')
            <x-filament::button type="button" color="gray" size="sm" wire:click="{{ $toggleMethod }}">
                Mostra JSON
            </x-filament::button>
            @else
            <x-filament::button type="button" color="gray" size="sm" wire:click="{{ $toggleMethod }}">
                Mostra HTML
            </x-filament::button>
            @endif
        </div>
        @endif
    </div>

    @if (! $canEdit)
    <div class="text-xs text-gray-600">Non hai i permessi per modificare questo contenuto.</div>
    @endif

    @if (! empty($error))
    <div class="text-xs text-danger-600">{{ $error }}</div>
    @endif
</div>