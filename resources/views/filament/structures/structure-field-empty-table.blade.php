@php
    $tableSize = $tableSize ?? null;

    $cols = 9;
    $rows = 6;

    if (is_string($tableSize) && preg_match('/^\s*(\d+)\s*x\s*(\d+)\s*$/i', $tableSize, $m)) {
        $cols = max(1, (int) $m[1]);
        $rows = max(1, (int) $m[2]);
    }

    $cols = min($cols, 24);
    $rows = min($rows, 16);
@endphp

<div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
    <div class="border-b border-gray-200 px-3 py-2 text-xs text-gray-500 dark:border-gray-700 dark:text-gray-400">
        Tabella vuota{{ is_string($tableSize) && trim($tableSize) !== '' ? ' (' . trim($tableSize) . ')' : '' }}
    </div>

    <div class="overflow-auto p-2">
        <table class="border-collapse">
            <tbody>
                @for ($r = 0; $r < $rows; $r++)
                    <tr>
                        @for ($c = 0; $c < $cols; $c++)
                            <td class="h-5 w-8 border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800"></td>
                        @endfor
                    </tr>
                @endfor
            </tbody>
        </table>
    </div>
</div>
