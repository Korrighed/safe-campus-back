<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Login;

/**
 * Date de derniere connexion, base de la purge des comptes inactifs.
 * Decouvert automatiquement par Laravel (methodes handle* typees).
 */
class EnregistreDerniereConnexion
{
    public function handleLogin(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        // Requete directe : ni updated_at, ni evenement de modele. Sinon
        // chaque connexion ajouterait une ligne `User.modifie` a l'audit.
        User::whereKey($event->user->getKey())->update(['derniere_connexion_at' => now()]);
    }
}
