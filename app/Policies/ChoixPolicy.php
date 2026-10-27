<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Choix;
use App\Models\User;

/**
 * Choix : aligne sur la scene qui le porte.
 */
class ChoixPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Redacteur || $user->role === UserRole::Relecteur;
    }

    public function view(User $user, Choix $choix): bool
    {
        return (new ScenePolicy())->view($user, $choix->scene);
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Redacteur;
    }

    public function update(User $user, Choix $choix): bool
    {
        return (new ScenePolicy())->update($user, $choix->scene);
    }

    public function delete(User $user, Choix $choix): bool
    {
        return (new ScenePolicy())->delete($user, $choix->scene);
    }
}
