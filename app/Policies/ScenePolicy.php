<?php

namespace App\Policies;

use App\Enums\EtatHistoire;
use App\Enums\UserRole;
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
            || ($this->estRedacteur($user)
                && $scene->histoires()
                    ->where('histoires.user_id', $user->id)
                    ->where('histoires.etat', EtatHistoire::Brouillon->value)
                    ->exists());
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
}
