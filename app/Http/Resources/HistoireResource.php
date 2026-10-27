<?php

namespace App\Http\Resources;

use App\Models\Histoire;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Le graphe complet d'une histoire publiee, joue cote front sans nouvel appel.
 *
 * `scenes` (et leur pivot) doit etre eager-charge : voir HistoireParcoursService.
 * Ni l'etat, ni l'auteur, ni l'ordre des scenes ne sortent : back-office uniquement.
 *
 * @mixin Histoire
 */
class HistoireResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'ref' => $this->ref,
            'titre' => $this->titre,
            'scene_initiale_id' => $this->scenes->first(fn ($scene) => (bool) $scene->pivot->getAttribute('est_initiale'))?->id,
            'scenes' => SceneResource::collection($this->scenes),
        ];
    }
}
