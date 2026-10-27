<?php

namespace App\Console\Commands;

use App\Services\NettoyageScenesService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('scenes:purger-orphelines {--simulation : Liste les scenes concernees sans rien supprimer}')]
#[Description('Supprime les scenes qu\'aucune histoire n\'utilise, creees il y a plus de '.NettoyageScenesService::DELAI_MINIMUM_HEURES.' heures')]
class PurgerScenesOrphelines extends Command
{
    public function handle(NettoyageScenesService $service): int
    {
        $simulation = (bool) $this->option('simulation');
        $ids = $service->purgerOrphelines($simulation);

        // Identifiants uniquement : ni titre ni texte de scene dans la sortie.
        foreach ($ids as $id) {
            $this->line(($simulation ? 'A supprimer : scene #' : 'Supprimee : scene #').$id);
        }

        $this->info(count($ids).' scene(s) concernee(s).');

        return self::SUCCESS;
    }
}
