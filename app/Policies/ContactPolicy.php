<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Contact;
use App\Models\User;

/**
 * Contacts/Téléphones : webmaster uniquement — voir docs/schema_bd.md § Rôles.
 */
class ContactPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Webmaster;
    }

    public function view(User $user, Contact $contact): bool
    {
        return $user->role === UserRole::Webmaster;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Webmaster;
    }

    public function update(User $user, Contact $contact): bool
    {
        return $user->role === UserRole::Webmaster;
    }

    public function delete(User $user, Contact $contact): bool
    {
        return $user->role === UserRole::Webmaster;
    }
}
