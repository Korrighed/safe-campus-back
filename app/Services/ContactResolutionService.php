<?php

namespace App\Services;

use App\Enums\ChoixIssue;
use App\Models\Choix;
use App\Models\Contact;
use App\Models\Histoire;
use Illuminate\Database\Eloquent\Collection;

/**
 * Resolution des contacts affiches en fin de parcours defavorable. Voir
 * docs/schema_bd.md § Choix — Resolution des contacts en fin de parcours.
 */
class ContactResolutionService
{
    /**
     * @return Collection<int, Contact>
     */
    public function resoudre(Histoire $histoire, Choix $choix): Collection
    {
        if ($choix->issue !== ChoixIssue::Defavorable) {
            return Contact::query()->whereRaw('1 = 0')->get();
        }

        // La bifurcation thematique de la scene prime sur celle de l'histoire.
        $sousThemeIds = $choix->scene->sous_theme_id !== null
            ? [$choix->scene->sous_theme_id]
            : $histoire->sousThemes()->pluck('sous_themes.id')->all();

        if ($sousThemeIds === []) {
            return Contact::query()->whereRaw('1 = 0')->get();
        }

        // PostgreSQL exige que la colonne d'ORDER BY figure dans le SELECT des
        // qu'il y a du DISTINCT. On trie donc sur le pivot avant de dedupliquer
        // en PHP (un contact peut correspondre a plusieurs sous-themes qualifies).
        return Contact::query()
            ->actif()
            ->join('contact_sous_theme', 'contacts.id', '=', 'contact_sous_theme.contact_id')
            ->whereIn('contact_sous_theme.sous_theme_id', $sousThemeIds)
            ->select('contacts.*', 'contact_sous_theme.ordre as pivot_ordre')
            ->orderBy('pivot_ordre')
            ->get()
            ->unique('id')
            ->values();
    }
}
