<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Le mappature policy per l'applicazione.
     */
    protected $policies = [
        User::class => UserPolicy::class,
    ];

    /**
     * Registra i servizi di autenticazione/autorizzazione.
     */
    public function boot(): void
    {
        //
    }
}
