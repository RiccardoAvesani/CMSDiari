@php
    $rows = (int) ($rows ?? 0);
    $cols = (int) ($cols ?? 0);

    if ($rows <= 0) {
        $rows = 3;
    }

    if ($cols <= 0) {
        $cols = 3;
    }

    $rows = min($rows, 20);
    $cols = min($cols, 12);

    $helperText = is_string($helper_text ?? null) ? trim($helper_text) : null;
@endphp

<div class="space-y-1">
    <div class="overflow-auto rounded-md border border-gray-200 dark:border-gray-800">
        <table class="w-full border-collapse text-xs">
            @for ($r = 0; $r < $rows; $r++)
                <tr>
                    @for ($c = 0; $c < $cols; $c++)
                        <td class="border border-gray-200 p-2 dark:border-gray-800">&nbsp;</td>
                    @endfor
                </tr>
            @endfor
        </table>
    </div>

    <div class="text-11px text-gray-500">
        Tabella vuota {{ $rows }} x {{ $cols }}
    </div>

    @if(! blank($helperText))
        <div class="text-11px text-gray-500">
            {{ $helperText }}
        </div>
    @endif
</div>
