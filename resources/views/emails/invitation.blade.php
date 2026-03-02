@component('mail::message')
# {{ $invitation->subject ?? (config('app.name') . ' - Invito') }}

@component('mail::panel')
{!! nl2br(e($invitation->message ?? 'Sei stato invitato ad accedere alla piattaforma. Usa il pulsante qui sotto per completare la registrazione.')) !!}
@endcomponent

@component('mail::button', ['url' => $openUrl])
Apri Invito
@endcomponent

@if(!empty($expiryDays))
Se non completi la registrazione entro {{ $expiryDays }} giorni, potrei chiederti di farti reinviare un nuovo Invito.
@endif

Se non hai richiesto questo Invito, puoi ignorare questo messaggio.

@slot('subcopy')
Se hai problemi a cliccare sul pulsante “Apri Invito”, copia e incolla l'URL qui sotto nel browser:
<span class="break-all">[{{ $openUrl }}]({{ $openUrl }})</span>
@endslot

{{-- Pixel best-effort (molti client lo bloccano comunque) --}}
@if(!empty($pixelUrl))
<img src="{{ $pixelUrl }}" alt="" width="1" height="1" style="display:none;">
@endif
@endcomponent