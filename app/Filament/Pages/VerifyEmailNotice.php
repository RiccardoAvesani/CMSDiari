<?php

namespace App\Filament\Pages;

use Filament\Notifications\Notification;
use Filament\Pages\SimplePage;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;

class VerifyEmailNotice extends SimplePage
{
    public function getView(): string
    {
        return 'filament.pages.verify-email-notice';
    }

    public function resend(): void
    {
        /** @var (\Illuminate\Contracts\Auth\Authenticatable&MustVerifyEmail)|null $user */
        $user = Auth::user();

        if (! $user) {
            return;
        }

        if ($user->hasVerifiedEmail()) {
            Notification::make()
                ->title('E-mail già verificata')
                ->success()
                ->send();

            return;
        }

        $user->sendEmailVerificationNotification();

        Notification::make()
            ->title('E-mail di verifica inviata')
            ->body('Controlla la posta in arrivo (e la cartella spam!).')
            ->success()
            ->send();
    }
}
