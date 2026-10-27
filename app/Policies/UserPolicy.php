<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

/**
 * Utilisateurs : admin uniquement — voir docs/schema_bd.md § Rôles. L'admin
 * valide les inscriptions et attribue les roles, il ne cree pas de compte.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function view(User $user, User $model): bool
    {
        return $user->role === UserRole::Admin && $model->role !== UserRole::Visiteur;
    }

    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Jamais son propre compte : un admin ne peut pas se retirer ses droits.
     * Corollaire : le dernier admin ne peut pas perdre son role, seul un autre
     * admin pourrait le lui retirer. Le compte visiteur (API front) est hors
     * perimetre.
     */
    public function update(User $user, User $model): bool
    {
        return $user->role === UserRole::Admin
            && ! $user->is($model)
            && $model->role !== UserRole::Visiteur;
    }

    /**
     * Refus d'une demande ou suppression d'un compte actif — ses histoires
     * sont alors transferees (SuppressionCompteService). Jamais son propre
     * compte (le dernier admin ne peut donc pas disparaitre), jamais le
     * compte visiteur.
     */
    public function delete(User $user, User $model): bool
    {
        return $user->role === UserRole::Admin
            && ! $user->is($model)
            && $model->role !== UserRole::Visiteur;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
