<?php

namespace App\Providers;

use App\Listeners\ActivateUserAfterEmailVerified;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Verified::class => [
            ActivateUserAfterEmailVerified::class,
        ],
    ];

    public function boot(): void
    {
        //
    }
}
