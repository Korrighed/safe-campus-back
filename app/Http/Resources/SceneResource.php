<?php

namespace App\Http\Resources;

use App\Models\Scene;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `titre` ne sort pas : libelle de back-office, jamais affiche au joueur.
 * `media` vaut null sans image, ou si le media est inactif ou n'est pas une image.
 *
 * @mixin Scene
 */
class SceneResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'dialogue_text' => $this->dialogue_text,
            'media' => $this->media ? new MediaResource($this->media) : null,
            'choix' => ChoixResource::collection($this->choix),
        ];
    }
}
