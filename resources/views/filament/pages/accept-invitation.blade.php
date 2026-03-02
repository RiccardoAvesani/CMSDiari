<x-filament-panels::page.simple>
    @if ($hasProblem)
    <div style="display:flex;flex-direction:column;gap:1.25rem;">
        <div style="
                border-left: 4px solid #18181b;
                margin: 0;
                background-color: #f8fafc;
                border-radius: 12px;
                padding: 16px;
            ">
            <div style="font-weight:600;margin-bottom:.25rem;color:#111827;">
                {{ $problemTitle }}
            </div>

            <div style="color:#4b5563;">
                {{ $problemBody }}
            </div>
        </div>

        <div style="display:flex;flex-wrap:wrap;gap:.75rem;">
            <x-filament::button tag="a" href="{{ route('login') }}" color="primary">
                Vai alla login
            </x-filament::button>

            <x-filament::button tag="a" href="javascript:history.back()" color="gray">
                Indietro
            </x-filament::button>
        </div>
    </div>
    @else
    <div style="display: flex; flex-direction: column; gap: 1.25rem;">
        <form wire:submit.prevent="submit" style="display: flex; flex-direction: column; gap: 1.25rem;">
            {{ $this->form }}

            <div style="margin-top: .25rem;">
                <x-filament::button type="submit">
                    Crea Account
                </x-filament::button>
            </div>
        </form>
    </div>
    @endif
</x-filament-panels::page.simple>