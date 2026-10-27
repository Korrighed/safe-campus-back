<?php

namespace App\Http\Resources;

use App\Models\SousTheme;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Version legere d'un sous-theme pour les cartes de navigation (accueil) et
 * le menu (SideNav/NavDrawer). `histoire_ref` : premiere histoire publiee, ou
 * null (un sous-theme en a au plus une cote front).
 *
 * @mixin SousTheme
 */
class SousThemeSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'ref' => $this->ref,
            'libelle' => $this->libelle,
            'resume' => $this->resume,
            'ordre' => $this->ordre,
            'histoire_ref' => $this->whenLoaded('histoires', fn () => $this->histoires->first()?->ref),
        ];
    }
}
