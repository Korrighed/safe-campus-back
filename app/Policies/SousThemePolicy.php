<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\SousTheme;
use App\Models\User;

/**
 * Thèmes/Sous-thèmes : webmaster uniquement — voir docs/schema_bd.md § Rôles.
 */
class SousThemePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Webmaster;
    }

    public function view(User $user, SousTheme $sousTheme): bool
    {
        return $user->role === UserRole::Webmaster;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Webmaster;
    }

    public function update(User $user, SousTheme $sousTheme): bool
    {
        return $user->role === UserRole::Webmaster;
    }

    public function delete(User $user, SousTheme $sousTheme): bool
    {
        return $user->role === UserRole::Webmaster;
    }
}
