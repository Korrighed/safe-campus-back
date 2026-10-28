<?php

namespace App\Services;

use App\Models\Choix;
use App\Models\Scene;

/**
 * Supprime les scenes qu'aucune histoire n'utilise plus. Les choix partent
 * avec elles (cascade) ; chaque suppression passe par le modele et apparait
 * donc dans l'audit (Scene.supprime, sans contenu).
 */
class NettoyageScenesService
{
    /**
     * Scenes creees depuis moins longtemps que ce delai sont epargnees par la
     * commande : une scene en cours de creation ne doit jamais etre purgee.
     */
    public const DELAI_MINIMUM_HEURES = 24;

    /**
     * Supprime, parmi $ids, les scenes sans histoire.
     *
     * @param  list<int>  $ids
     * @return list<int> identifiants supprimes
     */
    public function supprimerOrphelinesParmi(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $supprimees = [];

        Scene::query()
            ->whereIn('id', $ids)
            ->whereDoesntHave('histoires')
            ->get()
            ->each(function (Scene $scene) use (&$supprimees): void {
                $supprimees[] = $scene->getKey();
                $this->retirerChoixVisantLaScene($scene);
                $scene->delete();
            });

        return $supprimees;
    }

    /**
     * Toutes les scenes orphelines creees il y a plus de DELAI_MINIMUM_HEURES.
     *
     * @return list<int> identifiants concernes
     */
    public function purgerOrphelines(bool $simulation = false): array
    {
        $scenes = Scene::query()
            ->whereDoesntHave('histoires')
            ->where('created_at', '<', now()->subHours(self::DELAI_MINIMUM_HEURES))
            ->get();

        if (! $simulation) {
            $scenes->each(function (Scene $scene): void {
                $this->retirerChoixVisantLaScene($scene);
                $scene->delete();
            });
        }

        return $scenes->modelKeys();
    }

    /**
     * Supprime les choix qui visent $scene depuis une scene partagee avec une
     * histoire qui ne contient pas $scene. Ils etaient invisibles dans ces
     * histoires (cible hors histoire) : sans cela, la suppression les ferait
     * apparaitre comme des choix sans suite ni issue dans une histoire
     * publiee ou verrouillee. Les choix des scenes qui ne servent que
     * l'histoire d'ou la scene est retiree restent, a completer.
     */
    public function retirerChoixVisantLaScene(Scene $scene): void
    {
        Choix::query()
            ->where('next_scene_id', $scene->getKey())
            ->whereHas('scene.histoires', fn ($histoires) => $histoires->whereDoesntHave(
                'scenes',
                fn ($scenes) => $scenes->whereKey($scene->getKey()),
            ))
            ->get()
            ->each->delete();
    }
}
