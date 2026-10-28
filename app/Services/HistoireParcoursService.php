<?php

namespace App\Services;

use App\Enums\ChoixIssue;
use App\Enums\MediaType;
use App\Models\Choix;
use App\Models\Histoire;
use App\Models\Media;
use App\Models\Scene;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Prepare une histoire pour le front : le graphe complet (scenes, choix,
 * images) en une seule lecture, contacts de fin defavorable compris. Voir
 * docs/api.md.
 */
class HistoireParcoursService
{
    public function __construct(private readonly ContactResolutionService $contacts) {}

    public function charger(Histoire $histoire): Histoire
    {
        $histoire->load([
            'scenes' => fn ($query) => $query->orderBy('histoire_scene.ordre')->orderBy('scenes.id'),
            /** @param BelongsTo<Media, Scene> $query */
            'scenes.media' => fn ($query) => $query->actif()->where('type', MediaType::Image),
            'scenes.choix' => fn ($query) => $query->orderBy('id'),
        ]);

        $idsScenes = $histoire->scenes->modelKeys();

        foreach ($histoire->scenes as $scene) {
            // La liaison fait autorite sur la composition d'une histoire : un
            // choix qui mene a une scene non rattachee est ecarte plutot que
            // de renvoyer au front un next_scene_id introuvable.
            $scene->setRelation('choix', $scene->choix
                ->filter(fn (Choix $choix) => $choix->next_scene_id === null
                    || in_array($choix->next_scene_id, $idsScenes, true))
                ->values());

            foreach ($scene->choix as $choix) {
                if ($choix->issue !== ChoixIssue::Defavorable) {
                    continue;
                }

                $choix->setRelation('scene', $scene);
                $choix->setRelation('contacts', $this->contacts->resoudre($histoire, $choix)
                    ->load(['telephones' => fn ($query) => $query->actif()]));
            }
        }

        return $histoire;
    }
}
