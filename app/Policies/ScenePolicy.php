<?php

namespace App\Policies;

use App\Enums\EtatHistoire;
use App\Enums\UserRole;
use App\Models\Histoire;
use App\Models\Scene;
use App\Models\User;

/**
 * Scenes : alignees sur les histoires qui les contiennent.
 */
class ScenePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->estRedacteur($user) || $this->estRelecteur($user);
    }

    public function view(User $user, Scene $scene): bool
    {
        return $this->estRelecteur($user)
            || ($this->estRedacteur($user)
                && $scene->histoires()->where('histoires.user_id', $user->id)->exists());
    }

    public function create(User $user): bool
    {
        return $this->estRedacteur($user);
    }

    public function update(User $user, Scene $scene): bool
    {
        return $this->estRelecteur($user)
            || ($this->estRedacteur($user) && $this->modifiablePar($user, $scene));
    }

    public function delete(User $user, Scene $scene): bool
    {
        return $this->update($user, $scene);
    }

    private function estRedacteur(User $user): bool
    {
        return $user->role === UserRole::Redacteur;
    }

    private function estRelecteur(User $user): bool
    {
        return $user->role === UserRole::Relecteur;
    }

    private function modifiablePar(User $user, Scene $scene): bool
    {
        $histoires = $scene->histoires()->get(['histoires.id', 'histoires.user_id', 'histoires.etat']);

        return $histoires->isNotEmpty()
            && $histoires->every(fn (Histoire $histoire): bool => (int) $histoire->user_id === (int) $user->id
                && $histoire->etat === EtatHistoire::Brouillon);
    }
}
