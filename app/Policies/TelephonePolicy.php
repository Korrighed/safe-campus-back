<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Telephone;
use App\Models\User;

/**
 * Contacts/Téléphones : webmaster uniquement — voir docs/schema_bd.md § Rôles.
 */
class TelephonePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Webmaster;
    }

    public function view(User $user, Telephone $telephone): bool
    {
        return $user->role === UserRole::Webmaster;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Webmaster;
    }

    public function update(User $user, Telephone $telephone): bool
    {
        return $user->role === UserRole::Webmaster;
    }

    public function delete(User $user, Telephone $telephone): bool
    {
        return $user->role === UserRole::Webmaster;
    }
}
