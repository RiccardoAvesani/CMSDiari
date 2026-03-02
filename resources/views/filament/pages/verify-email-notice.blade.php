<x-filament-panels::page.simple>
    <div style="display: flex; flex-direction: column; gap: 1.25rem;">
        <div style="display: flex; flex-direction: column; gap: .75rem;">
            <p class="fi-text-sm fi-text-gray-600">
                Ti abbiamo inviato un link di verifica per l'indirizzo e-mail che hai inserito. Aprilo per attivare l'Account.
            </p>

            @if (session('status'))
            <div class="fi-rounded-lg fi-bg-success-50 fi-p-4 fi-text-sm fi-text-success-700">
                {{ session('status') }}
            </div>
            @endif
        </div>

        <div>
            <x-filament::button wire:click="resend" type="button">
                Reinvia e-mail di verifica
            </x-filament::button>
        </div>
    </div>
</x-filament-panels::page.simple>