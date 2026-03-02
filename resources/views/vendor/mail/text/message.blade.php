@component('mail::layout')
    {{-- Header --}}
    @slot('header')
        @component('mail::header', ['url' => config('app.url')])
CMS Diari
        @endcomponent
    @endslot

    {{-- Body --}}
{{ $slot }}

    {{-- Subcopy --}}
    @isset($subcopy)
        @slot('subcopy')
            @component('mail::subcopy')
{{ $subcopy }}
            @endcomponent
        @endslot
    @endisset

    {{-- Footer --}}
    @slot('footer')
        @component('mail::footer')
© {{ date('Y') }} CMS Diari. Tutti i diritti riservati.
        @endcomponent
    @endslot
@endcomponent
