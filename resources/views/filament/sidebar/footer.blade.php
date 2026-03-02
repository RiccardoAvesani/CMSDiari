<div class="cmsdiari-sidebar-footer">
    <div class="cmsdiari-divider"></div>

    <div class="cmsdiari-sidebar-footer-inner">
        @php
        $user = auth()->user();
        $canSeeSettings = $user && (
        str_starts_with((string) $user->role, 'admin')
        || str_starts_with((string) $user->role, 'internal')
        );
        @endphp

        @if ($canSeeSettings)
        <x-filament::button
            tag="a"
            href="{{ \App\Filament\Resources\Settings\SettingResource::getUrl('index') }}"
            icon="heroicon-o-cog-6-tooth"
            color="gray"
            outlined
            class="w-full">
            Impostazioni
        </x-filament::button>

        <div class="cmsdiari-divider"></div>
        @endif

        <div class="cmsdiari-logo-box">
            <img
                class="cmsdiari-logo-img"
                src="{{ asset('images/placeholder-cmsdiari.svg') }}"
                alt="Logo CMS Diari placeholder">
        </div>

        <div class="cmsdiari-divider"></div>

        <div class="cmsdiari-logo-box">
            <img
                class="cmsdiari-logo-img"
                src="{{ asset('images/placeholder-fabbrica.svg') }}"
                alt="Logo La Fabbrica placeholder">
        </div>
    </div>
</div>