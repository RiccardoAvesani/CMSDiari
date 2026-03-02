<x-filament-panels::page.simple>
    <div style="display: flex; flex-direction: column; gap: 1.25rem;">
        <div style="display: flex; flex-direction: column; gap: .75rem;">
            <p class="fi-text-sm fi-text-gray-600">
                Ti abbiamo inviato un messaggio con un link per confermare l'indirizzo e-mail che hai inserito.
                Aprilo per attivare l'Account.
            </p>

            <p class="fi-text-sm fi-text-gray-600">
                Se non lo trovi, controlla la cartella spam oppure richiedi l'invio di una nuova e-mail dalla pagina di verifica.
            </p>
        </div>

        <div style="display:flex;flex-wrap:wrap;gap:.75rem;">
            <x-filament::button tag="a" href="{{ route('verification.notice') }}" color="primary">
                Vai alla pagina di verifica
            </x-filament::button>

            <x-filament::button tag="a" href="{{ url('/') }}" color="gray">
                Torna alla home
            </x-filament::button>
        </div>
    </div>
</x-filament-panels::page.simple>