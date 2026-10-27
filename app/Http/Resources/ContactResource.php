<?php

namespace App\Http\Resources;

use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Contact
 */
class ContactResource extends JsonResource
{
    /**
     * `telephones` doit etre eager-charge par le controller.
     */
    public function toArray(Request $request): array
    {
        return [
            'ref' => $this->ref,
            'nom' => $this->nom,
            'prenom' => $this->prenom,
            'mail' => $this->mail,
            'localisation' => $this->localisation,
            'site_web' => $this->site_web,
            'horaires' => $this->horaires,
            'remarques' => $this->remarques,
            'gratuit' => $this->gratuit,
            'anonyme' => $this->anonyme,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'telephones' => TelephoneResource::collection($this->whenLoaded('telephones')),
        ];
    }
}
