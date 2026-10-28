<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\AuditService;
use Filament\Events\Auth\Registered;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

/**
 * Connexions, echecs, deconnexions et inscriptions dans le canal `audit`.
 * Decouvert automatiquement par Laravel (methodes handle* typees).
 */
class AuditAuthentification
{
    public function __construct(private readonly AuditService $audit) {}

    public function handleLogin(Login $event): void
    {
        $this->audit->ecrire('auth.connexion', $event->user instanceof User ? $event->user : null);
    }

    public function handleLogout(Logout $event): void
    {
        $this->audit->ecrire('auth.deconnexion', $event->user instanceof User ? $event->user : null);
    }

    public function handleFailed(Failed $event): void
    {
        // Jamais l'adresse saisie (elle peut contenir un mot de passe tape au
        // mauvais endroit) : l'identifiant du compte s'il existe, sinon rien.
        $compte = $event->user instanceof User ? $event->user : null;

        $this->audit->ecrire('auth.echec', null, [
            'compte_id' => $compte?->getKey(),
            'compte_connu' => $compte !== null,
        ]);
    }

    public function handleRegistered(Registered $event): void
    {
        $user = $event->getUser();

        $this->audit->ecrire('auth.inscription', $user instanceof User ? $user : null);
    }
}
