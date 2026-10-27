<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Theme;
use App\Models\User;

/**
 * Thèmes/Sous-thèmes : webmaster uniquement — voir docs/schema_bd.md § Rôles.
 */
class ThemePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Webmaster;
    }

    public function view(User $user, Theme $theme): bool
    {
        return $user->role === UserRole::Webmaster;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Webmaster;
    }

    public function update(User $user, Theme $theme): bool
    {
        return $user->role === UserRole::Webmaster;
    }

    public function delete(User $user, Theme $theme): bool
    {
        return $user->role === UserRole::Webmaster;
    }
}
