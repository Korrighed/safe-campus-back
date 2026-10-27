<?php

namespace Database\Seeders;

use App\Models\Theme;
use Illuminate\Database\Seeder;

/**
 * Taxonomie + presentation. `libelle` est le libelle long (drawer, recherche),
 * `libelle_court` celui de la nav/tags — repris du front (app/data/themes.js),
 * voir docs/textes-front.md.
 */
class ThemeSeeder extends Seeder
{
    /**
     * @var list<array{ref: string, libelle: string, libelle_court: string, ordre: int}>
     */
    private const THEMES = [
        ['ref' => 'addictions', 'libelle' => 'Conduites addictives', 'libelle_court' => 'Addictions', 'ordre' => 0],
        ['ref' => 'sante_mentale', 'libelle' => 'Santé mentale', 'libelle_court' => 'Santé mentale', 'ordre' => 1],
        ['ref' => 'vss', 'libelle' => 'Violences', 'libelle_court' => 'Violences', 'ordre' => 2],
    ];

    public function run(): void
    {
        foreach (self::THEMES as $theme) {
            Theme::updateOrCreate(['ref' => $theme['ref']], $theme);
        }
    }
}
