@php
    $message = (string) ($message ?? '');
@endphp

<div class="rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-600 shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
    {{ $message }}
</div>
