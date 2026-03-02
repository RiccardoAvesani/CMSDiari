<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Verified;

class ActivateUserAfterEmailVerified
{
    public function handle(Verified $event): void
    {
        $user = $event->user;

        // Qui mi assicuro di avere il mio Eloquent model, altrimenti non posso fare save().
        if (! $user instanceof User) {
            return;
        }

        if (is_null($user->email_verified_at)) {
            $user->email_verified_at = now();
            $dirty = true;
        }

        // Status del dominio: active / blocked / deleted
        if ($user->status === 'blocked') {
            $user->status = 'active';
        }

        if ($user->isDirty()) {
            $user->save();
        }
    }
}
