<?php

namespace App\Providers;

use Filament\Notifications\Notification;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {

        Gate::policy(\App\Models\User::class, \App\Policies\UserPolicy::class);

        // Permetti TUTTO agli admin
        Gate::before(function ($user, $ability) {
            if ($user && str_starts_with($user->role ?? '', 'admin|')) {
                return true; // Admin può fare tutto
            }
        });

        VerifyEmail::toMailUsing(function (object $notifiable, string $url) {
            return (new MailMessage)
                ->subject('Conferma il tuo indirizzo E-mail')
                ->greeting('Ciao!')
                ->line("Hai ricevuto questo messaggio per verificare l'indirizzo E-mail del tuo Account su CMS Diari.")
                ->action('Conferma indirizzo E-mail', $url)
                ->line('Se non hai richiesto tu questa operazione, puoi ignorare questo messaggio.');
        });

        // Toast: di default non devono sparire da soli
        Notification::configureUsing(function (Notification $notification): void {
            $notification->persistent();
        });

        FilamentView::registerRenderHook(
            PanelsRenderHook::SIDEBAR_FOOTER,
            fn(): View => view('filament.sidebar.footer'),
        );
    }
}
