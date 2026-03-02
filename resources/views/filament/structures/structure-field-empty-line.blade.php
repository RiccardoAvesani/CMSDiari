@php
    $message = (string) ($message ?? 'Riga vuota.');
@endphp

<div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400">
    {{ $message }}
</div>