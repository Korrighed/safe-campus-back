<?php

namespace App\Http\Resources;

use App\Models\SousTheme;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Sert /contact/{theme}/{slug} et /ressources/{theme}/{slug} : meme hero,
 * un seul endpoint.
 *
 * `theme` n'embarque que ref/libelle_court (le tag affiche) : le reste de
 * ThemeResource (resume, medias, sous_themes) n'a pas sa place sur la fiche.
 *
 * `histoires` : ref et titre des histoires publiees seulement, de quoi activer
 * le bouton « Suivre l'histoire » ; le graphe se lit sur /api/histoires/{ref}.
 *
 * @mixin SousTheme
 */
class SousThemeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'ref' => $this->ref,
            'libelle' => $this->libelle,
            'article' => $this->article,
            'intro_ressources' => $this->intro_ressources,
            'theme' => $this->whenLoaded('theme', fn () => [
                'ref' => $this->theme->ref,
                'libelle_court' => $this->theme->libelle_court,
            ]),
            'contacts' => ContactResource::collection($this->whenLoaded('contacts')),
            'documents' => MediaResource::collection($this->whenLoaded('documents')),
            'histoires' => $this->whenLoaded('histoires', fn () => $this->histoires
                ->map(fn ($histoire) => ['ref' => $histoire->ref, 'titre' => $histoire->titre])
                ->values()),
        ];
    }
}
