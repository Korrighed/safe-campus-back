<?php

namespace Tests\Feature\Database;

use App\Models\Choix;
use App\Models\Histoire;
use App\Models\Scene;
use App\Models\SousTheme;
use Illuminate\Database\QueryException;
use Tests\TestCase;

class HistoireTest extends TestCase
{
    public function test_le_ref_est_genere_depuis_le_titre(): void
    {
        $histoire = Histoire::factory()->create(['titre' => 'Soirée entre amis', 'ref' => null]);

        $this->assertSame('soiree_entre_amis', $histoire->ref);
    }

    public function test_le_ref_est_deduplique_en_cas_de_collision(): void
    {
        Histoire::factory()->create(['titre' => 'Soirée entre amis', 'ref' => null]);
        $seconde = Histoire::factory()->create(['titre' => 'Soirée entre amis', 'ref' => null]);

        $this->assertSame('soiree_entre_amis_2', $seconde->ref);
    }

    public function test_le_ref_ne_change_pas_a_l_edition(): void
    {
        $histoire = Histoire::factory()->create(['titre' => 'Titre initial']);
        $ref = $histoire->ref;

        $histoire->update(['titre' => 'Titre modifié']);

        $this->assertSame($ref, $histoire->fresh()->ref);
    }

    public function test_une_histoire_porte_plusieurs_sous_themes(): void
    {
        $histoire = Histoire::factory()->create();
        $sousThemes = SousTheme::factory()->count(2)->create();

        $histoire->sousThemes()->attach($sousThemes->pluck('id'));

        $this->assertCount(2, $histoire->sousThemes);
    }

    public function test_une_scene_peut_etre_partagee_entre_plusieurs_histoires(): void
    {
        $scene = Scene::factory()->create();
        $premiere = Histoire::factory()->create();
        $seconde = Histoire::factory()->create();

        $premiere->scenes()->attach($scene->id, ['est_initiale' => true]);
        $seconde->scenes()->attach($scene->id, ['est_initiale' => false]);

        $this->assertCount(2, $scene->histoires);
    }

    public function test_l_ordre_d_une_scene_est_propre_a_chaque_histoire(): void
    {
        $scene = Scene::factory()->create();
        $premiere = Histoire::factory()->create();
        $seconde = Histoire::factory()->create();

        $premiere->scenes()->attach($scene->id, ['ordre' => 1]);
        $seconde->scenes()->attach($scene->id, ['ordre' => 4]);

        $this->assertSame(1, $premiere->scenes()->first()->pivot->getAttribute('ordre'));
        $this->assertSame(4, $seconde->scenes()->first()->pivot->getAttribute('ordre'));
    }

    public function test_une_seule_scene_initiale_par_histoire(): void
    {
        $histoire = Histoire::factory()->create();
        $premiere = Scene::factory()->create();
        $seconde = Scene::factory()->create();

        $histoire->scenes()->attach($premiere->id, ['est_initiale' => true]);

        $this->expectException(QueryException::class);

        $histoire->scenes()->attach($seconde->id, ['est_initiale' => true]);
    }

    public function test_scene_initiale_retourne_la_bonne_scene(): void
    {
        $histoire = Histoire::factory()->create();
        $intro = Scene::factory()->create();
        $suite = Scene::factory()->create();

        $histoire->scenes()->attach($intro->id, ['est_initiale' => true]);
        $histoire->scenes()->attach($suite->id, ['est_initiale' => false]);

        $this->assertTrue($histoire->sceneInitiale()->is($intro));
    }

    public function test_un_choix_sans_scene_suivante_signifie_sortie_du_parcours(): void
    {
        $choix = Choix::factory()->favorable()->create();

        $this->assertNull($choix->next_scene_id);
        $this->assertNull($choix->nextScene);
    }

    public function test_une_scene_regroupe_ses_choix(): void
    {
        $scene = Scene::factory()->create();
        Choix::factory()->count(3)->create(['scene_id' => $scene->id]);

        $this->assertCount(3, $scene->choix);
    }
}
