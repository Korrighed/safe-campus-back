<?php

namespace App\Console\Commands;

use App\Services\SuppressionCompteService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('comptes:purger-inactifs {--simulation : Liste les comptes concernes sans rien supprimer}')]
#[Description('Supprime les comptes sans connexion depuis plus de '.SuppressionCompteService::INACTIVITE_ANNEES.' ans')]
class PurgerComptesInactifs extends Command
{
    public function handle(SuppressionCompteService $service): int
    {
        $resultat = $service->purgerInactifs($this->option('simulation'));

        foreach ($resultat['supprimes'] as $email) {
            $this->line(($this->option('simulation') ? 'A supprimer : ' : 'Supprime : ').$email);
        }

        foreach ($resultat['ignores'] as $email) {
            $this->warn("Ignore (a des histoires, repreneur a choisir par un admin) : {$email}");
        }

        $this->info(count($resultat['supprimes']).' compte(s) concerne(s), '.count($resultat['ignores']).' ignore(s).');

        return self::SUCCESS;
    }
}
