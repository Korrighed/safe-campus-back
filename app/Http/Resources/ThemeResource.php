<?php

namespace App\Http\Resources;

use App\Models\Theme;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Theme
 */
class ThemeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'ref' => $this->ref,
            'libelle' => $this->libelle,
            'libelle_court' => $this->libelle_court,
            'resume' => $this->resume,
            'ordre' => $this->ordre,
            'medias' => MediaResource::collection($this->whenLoaded('medias')),
            'sous_themes' => SousThemeSummaryResource::collection($this->whenLoaded('sousThemes')),
        ];
    }
}
