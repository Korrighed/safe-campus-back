<?php

namespace App\Policies;

use App\Enums\EtatHistoire;
use App\Enums\UserRole;
use App\Models\Histoire;
use App\Models\User;

/**
 * Histoires : le redacteur ne touche qu'a ses histoires en brouillon, le
 * relecteur a toutes — voir docs/cahier-des-charges-techniques.md § 4.1.
 */
class HistoirePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->estRedacteur($user) || $this->estRelecteur($user);
    }

    public function view(User $user, Histoire $histoire): bool
    {
        return $this->estRelecteur($user)
            || ($this->estRedacteur($user) && $this->estAuteur($user, $histoire));
    }

    /**
     * Le relecteur ne cree pas d'histoires (pour le moment).
     */
    public function create(User $user): bool
    {
        return $this->estRedacteur($user);
    }

    public function update(User $user, Histoire $histoire): bool
    {
        return $this->estRelecteur($user)
            || ($this->estRedacteur($user)
                && $this->estAuteur($user, $histoire)
                && $histoire->etat === EtatHistoire::Brouillon);
    }

    public function delete(User $user, Histoire $histoire): bool
    {
        return $this->update($user, $histoire);
    }

    /**
     * Autorise l'action groupee ; chaque histoire est ensuite controlee par delete().
     */
    public function deleteAny(User $user): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Etats que l'utilisateur peut poser sur cette histoire.
     *
     * @return list<EtatHistoire>
     */
    public function etatsAccessibles(User $user, Histoire $histoire): array
    {
        if ($this->estRelecteur($user)) {
            return EtatHistoire::cases();
        }

        if ($this->update($user, $histoire)) {
            return [EtatHistoire::Brouillon, EtatHistoire::Relecture];
        }

        return [];
    }

    private function estRedacteur(User $user): bool
    {
        return $user->role === UserRole::Redacteur;
    }

    private function estRelecteur(User $user): bool
    {
        return $user->role === UserRole::Relecteur;
    }

    private function estAuteur(User $user, Histoire $histoire): bool
    {
        return (int) $histoire->user_id === (int) $user->id;
    }
}
