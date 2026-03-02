<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Tutti possono vedere la lista utenti
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Tutti possono vedere il profilo di qualsiasi utente
     */
    public function view(User $user, User $model): bool
    {
        return true;
    }

    /**
     * Solo Admin ed Internal possono creare nuovi utenti
     */
    public function create(User $user): bool
    {
        return $user->role->isInternalRole() && $user->role->isAdminRole();
    }

    /**
     * Admin può modificare tutti i profili
     * Utente può modificare il proprio profilo
     */
    public function update(User $user, User $model): bool
    {
        if ($user->role->isAdminRole()) {
            return true;
        }

        return $user->id === $model->id;
    }

    /**
     * Solo Admin ed Internal possono eliminare Utenti
     */
    public function delete(User $user, User $model): bool
    {
        return $user->role->isAdminRole() || $user->role->isInternalRole();
    }

    /**
     * Solo Admin ed Internal possono ripristinare utenti eliminati
     */
    public function restore(User $user, User $model): bool
    {
        return $user->role->isAdminRole() || $user->role->isInternalRole();
    }

    /**
     * Solo Admin può eliminare definitivamente
     */
    public function forceDelete(User $user, User $model): bool
    {
        return $user->role->isAdminRole();
    }
}
