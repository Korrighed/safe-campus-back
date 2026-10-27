<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Scene;
use App\Models\User;

/**
 * Histoires/Scenes/Choix : redacteur uniquement — voir docs/schema_bd.md § Rôles.
 */
class ScenePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Redacteur;
    }

    public function view(User $user, Scene $scene): bool
    {
        return $user->role === UserRole::Redacteur;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Redacteur;
    }

    public function update(User $user, Scene $scene): bool
    {
        return $user->role === UserRole::Redacteur;
    }

    public function delete(User $user, Scene $scene): bool
    {
        return $user->role === UserRole::Redacteur;
    }
}
