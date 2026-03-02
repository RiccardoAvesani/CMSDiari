@php
$raw = $items ?? [];

if ($raw instanceof \Illuminate\Support\Collection) {
$raw = $raw->all();
}

$items = is_array($raw) ? $raw : [];
@endphp

<div class="space-y-2">
    @if (count($items) === 0)
    <div class="text-sm text-gray-600 dark:text-gray-300">
        Nessuna Tipologia Pagina configurata sul Modello Generico collegato.
    </div>
    @else
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="text-left text-gray-600 dark:text-gray-300">
                    <th class="py-2 pr-4">Posizione</th>
                    <th class="py-2 pr-4">Tipologia Pagina</th>
                    <th class="py-2 pr-4">Max occorrenze</th>
                </tr>
            </thead>
            <tbody class="text-gray-900 dark:text-gray-100">
                @foreach ($items as $row)
                <tr class="border-t border-gray-200 dark:border-gray-700">
                    <td class="py-2 pr-4">{{ (int) ($row['position'] ?? 0) }}</td>
                    <td class="py-2 pr-4">{{ (string) ($row['page_type_description'] ?? 'Tipologia') }}</td>
                    <td class="py-2 pr-4">{{ (int) ($row['page_type_max_pages'] ?? 1) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>