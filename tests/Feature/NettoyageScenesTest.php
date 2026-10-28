<?php

namespace Tests\Feature;

use App\Models\Choix;
use App\Models\Histoire;
use App\Models\Scene;
use Tests\TestCase;

class NettoyageScenesTest extends TestCase
{
    public function test_supprimer_une_histoire_supprime_ses_scenes_orphelines(): void
    {
        $histoire = Histoire::factory()->create();
        $intro = Scene::factory()->create();
        $salon = Scene::factory()->create();
        $histoire->scenes()->attach($intro->id, ['est_initiale' => true]);
        $histoire->scenes()->attach($salon->id);
        $choix = Choix::factory()->create(['scene_id' => $intro->id, 'next_scene_id' => $salon->id]);

        $histoire->delete();

        $this->assertModelMissing($intro);
        $this->assertModelMissing($salon);
        $this->assertModelMissing($choix);
    }

    public function test_une_scene_partagee_avec_une_autre_histoire_est_conservee(): void
    {
        $supprimee = Histoire::factory()->create();
        $autre = Histoire::factory()->create();
        $partagee = Scene::factory()->create();
        $exclusive = Scene::factory()->create();
        $supprimee->scenes()->attach($partagee->id, ['est_initiale' => true]);
        $supprimee->scenes()->attach($exclusive->id);
        $autre->scenes()->attach($partagee->id);

        $supprimee->delete();

        $this->assertModelExists($partagee);
        $this->assertModelMissing($exclusive);
    }

    public function test_le_choix_d_une_scene_conservee_vers_une_scene_supprimee_disparait(): void
    {
        $supprimee = Histoire::factory()->create();
        $autre = Histoire::factory()->create();
        $s1 = Scene::factory()->create();
        $s2 = Scene::factory()->create();
        $supprimee->scenes()->attach($s1->id, ['est_initiale' => true]);
        $autre->scenes()->attach($s1->id);
        $supprimee->scenes()->attach($s2->id);
        $choix = Choix::factory()->create(['scene_id' => $s1->id, 'next_scene_id' => $s2->id]);

        $supprimee->delete();

        $this->assertModelExists($s1);
        $this->assertModelMissing($s2);
        $this->assertModelMissing($choix);
    }

    public function test_la_commande_supprime_les_scenes_orphelines_anciennes(): void
    {
        $scene = Scene::factory()->create(['titre' => 'Titre confidentiel', 'created_at' => now()->subDays(3)]);

        $this->artisan('scenes:purger-orphelines')
            ->doesntExpectOutputToContain('Titre confidentiel')
            ->assertSuccessful();

        $this->assertModelMissing($scene);
    }

    public function test_la_commande_epargne_les_scenes_recentes_et_rattachees(): void
    {
        $recente = Scene::factory()->create();
        $ancienneRattachee = Scene::factory()->create(['created_at' => now()->subDays(3)]);
        $histoire = Histoire::factory()->create();
        $histoire->scenes()->attach($ancienneRattachee->id, ['est_initiale' => true]);

        $this->artisan('scenes:purger-orphelines')->assertSuccessful();

        $this->assertModelExists($recente);
        $this->assertModelExists($ancienneRattachee);
    }

    public function test_la_simulation_ne_supprime_rien(): void
    {
        $scene = Scene::factory()->create(['created_at' => now()->subDays(3)]);

        $this->artisan('scenes:purger-orphelines --simulation')->assertSuccessful();

        $this->assertModelExists($scene);
    }
}
