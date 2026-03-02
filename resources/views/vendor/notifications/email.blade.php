<x-mail::message>
{{-- Greeting --}}
@if (! empty($greeting))
# {{ $greeting }}
@else
# Ciao!
@endif

{{-- Intro Lines (callout come la prima mail) --}}
@component('mail::panel')
@foreach ($introLines as $line)
{{ $line }}

@endforeach
@endcomponent

{{-- Action Button --}}
@isset($actionText)
<?php
$color = match ($level) {
    'success', 'error' => $level,
    default => 'primary',
};
?>
@component('mail::button', ['url' => $actionUrl, 'color' => $color])
{{ $actionText }}
@endcomponent
@endisset

{{-- Outro Lines --}}
@foreach ($outroLines as $line)
{{ $line }}

@endforeach

{{-- Salutation --}}
@if (! empty($salutation))
{{ $salutation }}
@else
Cordiali saluti,<br>
{{ config('app.name') }}
@endif

{{-- Subcopy (Italiano) --}}
@isset($actionText)
@slot('subcopy')
Se hai problemi a cliccare sul pulsante “{{ $actionText }}”, copia e incolla l’URL qui sotto nel browser:
<span class="break-all">[{{ $displayableActionUrl }}]({{ $actionUrl }})</span>
@endslot
@endisset
</x-mail::message>
