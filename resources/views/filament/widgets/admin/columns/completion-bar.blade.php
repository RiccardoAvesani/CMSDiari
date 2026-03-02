@php
$raw = null;

// In Filament, nelle view dei Column è comune avere $getState() come Closure.
if (isset($getState) && $getState instanceof \Closure) {
$raw = $getState();
} elseif (isset($state)) {
$raw = $state instanceof \Closure ? $state() : $state;
}

$percent = (int) ($raw ?? 0);
$percent = max(0, min(100, $percent));

// MOCK: colore in base alla soglia (in futuro lo legheremo alla % reale di compilazione Template)
$barClass = match (true) {
$percent >= 85 => 'bg-success-600',
$percent >= 60 => 'bg-warning-500',
$percent >= 35 => 'bg-warning-600',
default => 'bg-danger-600',
};
@endphp

<div class="flex items-center gap-2 w-full">
    <div class="w-28 h-2 rounded-full bg-gray-200 dark:bg-gray-800 overflow-hidden">
        <div
            @class(['h-2', $barClass])
            @style(["width: {$percent}%"])></div>
    </div>

    <div class="text-xs text-gray-600 dark:text-gray-300 tabular-nums">
        {{ $percent }}%
    </div>
</div>