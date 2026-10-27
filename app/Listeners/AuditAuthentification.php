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
        // Email tente uniquement, jamais le mot de passe saisi.
        $this->audit->ecrire('auth.echec', null, [
            'email_tente' => $event->credentials['email'] ?? null,
        ]);
    }

    public function handleRegistered(Registered $event): void
    {
        $user = $event->getUser();

        $this->audit->ecrire('auth.inscription', $user instanceof User ? $user : null);
    }
}
