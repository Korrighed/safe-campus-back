<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Histoire;
use App\Models\User;

/**
 * Histoires/Scenes/Choix : redacteur uniquement — voir docs/schema_bd.md § Rôles.
 */
class HistoirePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Redacteur;
    }

    public function view(User $user, Histoire $histoire): bool
    {
        return $user->role === UserRole::Redacteur;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Redacteur;
    }

    public function update(User $user, Histoire $histoire): bool
    {
        return $user->role === UserRole::Redacteur;
    }

    public function delete(User $user, Histoire $histoire): bool
    {
        return $user->role === UserRole::Redacteur;
    }
}
