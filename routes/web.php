<?php

use App\Filament\Pages\AcceptInvitation;
use App\Filament\Pages\VerifyEmailNotice;
use App\Filament\Pages\VerifyEmailSent;
use App\Http\Controllers\InvitationPublicController;
use App\Http\Controllers\UserInitialsAvatarController;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// -----------------------------------------------------------------------------
// Alias "login": Laravel (middleware auth) si aspetta una route chiamata "login".
// -----------------------------------------------------------------------------
Route::get('/login', function () {
    return redirect()->route('filament.admin.auth.login');
})->name('login');

Route::post('/logout', function () {
    return redirect()->route('filament.admin.auth.logout');
})->name('logout');

Route::get('/', function () {
    return view('welcome');
});

// Avatar initials
Route::get('/avatars/initials/{user}.svg', UserInitialsAvatarController::class)
    ->name('avatars.initials');

// Invito: apertura + pixel
Route::get('/invitation/open/{accessToken}', AcceptInvitation::class)
    ->name('invitation.open');

Route::get('/invitation/pixel/{openToken}.gif', [InvitationPublicController::class, 'pixel'])
    ->name('invitation.pixel');

// -----------------------------------------------------------------------------
// Pagine “pubbliche” in stile Filament (non richiedono auth).
// Dopo registrazione vuoi mandare qui e far vedere solo il bottone Login.
// -----------------------------------------------------------------------------
Route::get('/email/verify', VerifyEmailNotice::class)
    ->name('verification.notice');

Route::get('/email/verify/sent', VerifyEmailSent::class)
    ->name('verification.sent');

// -----------------------------------------------------------------------------
// Verifica e-mail (link firmato) + login automatico + redirect dashboard + toast.
// -----------------------------------------------------------------------------
Route::get('/email/verify/{id}/{hash}', function (Request $request, string $id, string $hash) {
    // 1) Firma valida, ignorando eventuali query param aggiunti (tracking, ecc.)
    $ignore = array_values(array_diff(array_keys($request->query()), ['expires', 'signature']));

    if (! $request->hasValidSignatureWhileIgnoring($ignore)) {
        return redirect()
            ->route('verification.sent')
            ->with('status', 'Link di verifica non valido o scaduto. Puoi richiedere una nuova e-mail di verifica.');
    }

    /** @var User $user */
    $user = User::query()->findOrFail($id);

    // 2) Hash email corretto
    if (! hash_equals($hash, sha1($user->getEmailForVerification()))) {
        return redirect()
            ->route('verification.sent')
            ->with('status', 'Link di verifica non valido. Puoi richiedere una nuova e-mail di verifica.');
    }

    // 3) Verifica + evento (serve al listener ActivateUserAfterEmailVerified)
    if (! $user->hasVerifiedEmail()) {
        $user->markEmailAsVerified();
        event(new Verified($user));
    }

    // 4) Login automatico
    Auth::login($user);

    // 5) Toast Filament + redirect alla Dashboard
    Notification::make()
        ->title('Account attivato')
        ->body("Il tuo indirizzo e-mail è stato confermato. Ora puoi usare l'applicazione.")
        ->success()
        ->send();

    return redirect()->route('filament.admin.pages.dashboard');
})
    ->middleware(['throttle:6,1'])
    ->name('verification.verify');

// Reinvio e-mail di verifica: per utenti Bloccati che devono verificare l’e-mail prima di essere attivati (dopo registrazione con Invito).
Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return back()->with('status', 'E-mail di verifica inviata.');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');
