<?php

namespace Tests\Feature\Database;

use App\Models\SousTheme;
use App\Models\Theme;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TaxonomieTest extends TestCase
{
    public function test_un_theme_contient_plusieurs_sous_themes(): void
    {
        $theme = Theme::factory()->create(['ref' => 'addictions_test']);
        SousTheme::factory()->count(4)->create(['theme_id' => $theme->id]);

        $this->assertCount(4, $theme->sousThemes);
        $this->assertSame('addictions_test', $theme->sousThemes->first()->theme->ref);
    }

    public function test_le_sous_theme_porte_la_fiche(): void
    {
        $sousTheme = SousTheme::factory()->create([
            'ref' => 'alcool_test',
            'libelle' => 'Alcool',
            'article' => 'Contenu editorial de la fiche alcool.',
        ]);

        // Le sous-theme est la fiche : pas de table ni de relation separee.
        $this->assertSame('Alcool', $sousTheme->libelle);
        $this->assertSame('Contenu editorial de la fiche alcool.', $sousTheme->article);
        $this->assertFalse(method_exists($sousTheme, 'fiches'));
        $this->assertFalse(DB::getSchemaBuilder()->hasTable('fiches'));
        $this->assertFalse(DB::getSchemaBuilder()->hasTable('sous_theme_fiches'));
    }

    public function test_un_sous_theme_peut_exister_sans_article_redige(): void
    {
        $sousTheme = SousTheme::factory()->sansArticle()->create();

        $this->assertNull($sousTheme->article);
    }

    public function test_la_ref_d_un_theme_est_unique(): void
    {
        Theme::factory()->create(['ref' => 'vss_test']);

        $this->expectException(UniqueConstraintViolationException::class);

        Theme::factory()->create(['ref' => 'vss_test']);
    }

    public function test_la_ref_d_un_sous_theme_est_unique(): void
    {
        SousTheme::factory()->create(['ref' => 'urgences']);

        $this->expectException(UniqueConstraintViolationException::class);

        SousTheme::factory()->create(['ref' => 'urgences']);
    }

    public function test_permet_signalement_est_faux_par_defaut(): void
    {
        // Le signalement n'est ouvert que sur les sous-themes explicitement actives.
        $defaut = SousTheme::factory()->create();
        $ouvert = SousTheme::factory()->avecSignalement()->create();

        $this->assertFalse($defaut->permet_signalement);
        $this->assertTrue($ouvert->permet_signalement);
    }

    public function test_supprimer_un_theme_supprime_ses_sous_themes(): void
    {
        // Compare a la baseline plutot qu'a 0 : la taxonomie de reference
        // (migration 2026_07_31_120000_seed_taxonomie_themes_sous_themes)
        // peuple deja sous_themes independamment de ce test.
        $baseline = SousTheme::count();

        $theme = Theme::factory()->create();
        SousTheme::factory()->count(3)->create(['theme_id' => $theme->id]);

        $theme->delete();

        $this->assertSame($baseline, SousTheme::count());
    }
}
