@php
    $helperText = is_string($helper_text ?? null) ? trim($helper_text) : null;
@endphp

<div class="space-y-1">
    <input
        type="text"
        class="w-full rounded-md border-gray-300 bg-gray-50 text-sm shadow-sm dark:border-gray-700 dark:bg-gray-950"
        value="Riga vuota"
        disabled
    />

    @if(! blank($helperText))
        <div class="text-11px text-gray-500">
            {{ $helperText }}
        </div>
    @endif
</div>
