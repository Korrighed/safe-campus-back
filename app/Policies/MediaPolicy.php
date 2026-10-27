<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Media;
use App\Models\User;

/**
 * Médias : webmaster et rédacteur — voir docs/schema_bd.md § Rôles. Seule
 * ressource Filament partagée entre les deux : illustrations et documents
 * servent aussi bien l'annuaire que les histoires.
 */
class MediaPolicy
{
    private function autorise(User $user): bool
    {
        // Allowlist : ni l'admin (gestion des comptes uniquement) ni le visiteur.
        return in_array($user->role, [UserRole::Webmaster, UserRole::Redacteur], true);
    }

    public function viewAny(User $user): bool
    {
        return $this->autorise($user);
    }

    public function view(User $user, Media $media): bool
    {
        return $this->autorise($user);
    }

    public function create(User $user): bool
    {
        return $this->autorise($user);
    }

    public function update(User $user, Media $media): bool
    {
        return $this->autorise($user);
    }

    public function delete(User $user, Media $media): bool
    {
        return $this->autorise($user);
    }
}
