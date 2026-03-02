<!doctype html>
<html lang="it">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifica E-mail</title>

    @filamentStyles
</head>

<body class="antialiased">
    <x-filament-panels::page.simple>
        <div class="space-y-6">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">
                    Verifica la tua E-mail
                </h1>

                <p class="mt-2 text-sm text-gray-600">
                    Ti abbiamo inviato un link di verifica. Aprilo per attivare il tuo Account.
                </p>
            </div>

            @if (session('status'))
            <div class="rounded-lg bg-success-50 p-4 text-sm text-success-700">
                {{ session('status') }}
            </div>
            @endif

            <form method="POST" action="{{ route('verification.send') }}" class="space-y-4">
                @csrf

                <x-filament::button type="submit">
                    Reinvia E-mail di verifica
                </x-filament::button>
            </form>
        </div>
    </x-filament-panels::page.simple>

    @filamentScripts
</body>

</html>