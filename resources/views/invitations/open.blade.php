<!doctype html>
<html lang="it">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invito</title>
</head>

<body style="font-family: Arial, sans-serif; line-height: 1.4;">
    <h1>Invito valido</h1>

    <p>
        Stai aprendo un Invito per: <strong>{{ $invitation->email }}</strong>
    </p>

    @if($invitation->expires_at)
    <p>
        Scade il: {{ $invitation->expires_at->format('d/m/Y H:i') }}
    </p>
    @endif

    <p>
        La pagina di registrazione non è ancora implementata. Buona fortuna!
    </p>
</body>

</html>