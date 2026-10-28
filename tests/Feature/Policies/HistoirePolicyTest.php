<?php

namespace Tests\Feature\Policies;

use App\Enums\EtatHistoire;
use App\Enums\UserRole;
use App\Models\Choix;
use App\Models\Histoire;
use App\Models\Scene;
use App\Models\User;
use App\Policies\HistoirePolicy;
use Tests\TestCase;

class HistoirePolicyTest extends TestCase
{
    public function test_le_redacteur_modifie_son_histoire_en_brouillon_puis_ne_la_modifie_plus_en_relecture(): void
    {
        $redacteur = User::factory()->create(['role' => UserRole::Redacteur]);
        $histoire = Histoire::factory()->pour($redacteur)->create(['etat' => EtatHistoire::Brouillon]);

        $this->assertTrue($redacteur->can('view', $histoire));
        $this->assertTrue($redacteur->can('update', $histoire));

        $histoire->update(['etat' => EtatHistoire::Relecture]);

        $this->assertFalse($redacteur->can('update', $histoire));
        $this->assertTrue($redacteur->can('view', $histoire));
    }

    public function test_le_redacteur_ne_voit_ni_ne_modifie_ni_ne_supprime_l_histoire_d_un_autre(): void
    {
        $redacteur = User::factory()->create(['role' => UserRole::Redacteur]);
        $autre = Histoire::factory()->pour(User::factory()->create(['role' => UserRole::Redacteur]))->create();

        $this->assertFalse($redacteur->can('view', $autre));
        $this->assertFalse($redacteur->can('update', $autre));
        $this->assertFalse($redacteur->can('delete', $autre));
    }

    public function test_le_relecteur_voit_modifie_et_supprime_une_histoire_publiee_d_autrui_mais_ne_cree_pas(): void
    {
        $relecteur = User::factory()->create(['role' => UserRole::Relecteur]);
        $histoire = Histoire::factory()->pour(User::factory()->create(['role' => UserRole::Redacteur]))->create(['etat' => EtatHistoire::Publie]);

        $this->assertTrue($relecteur->can('view', $histoire));
        $this->assertTrue($relecteur->can('update', $histoire));
        $this->assertTrue($relecteur->can('delete', $histoire));
        $this->assertFalse($relecteur->can('create', Histoire::class));
    }

    public function test_un_relecteur_peut_creer_une_scene_mais_pas_une_histoire(): void
    {
        $relecteur = User::factory()->create(['role' => UserRole::Relecteur]);

        $this->assertTrue($relecteur->can('create', Scene::class));
        $this->assertTrue($relecteur->can('create', Choix::class));
        $this->assertFalse($relecteur->can('create', Histoire::class));
    }

    public function test_webmaster_admin_et_sans_role_n_accedent_pas_aux_histoires(): void
    {
        $webmaster = User::factory()->create(['role' => UserRole::Webmaster]);
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $sansRole = User::factory()->create(['role' => null]);

        $this->assertFalse($webmaster->can('viewAny', Histoire::class));
        $this->assertFalse($admin->can('viewAny', Histoire::class));
        $this->assertFalse($sansRole->can('viewAny', Histoire::class));
    }

    public function test_les_etats_accessibles_suivent_le_role_et_l_etat(): void
    {
        $policy = new HistoirePolicy;
        $relecteur = User::factory()->create(['role' => UserRole::Relecteur]);
        $redacteur = User::factory()->create(['role' => UserRole::Redacteur]);
        $brouillon = Histoire::factory()->pour($redacteur)->create(['etat' => EtatHistoire::Brouillon]);
        $relecture = Histoire::factory()->pour($redacteur)->create(['etat' => EtatHistoire::Relecture]);

        $this->assertSame(EtatHistoire::cases(), $policy->etatsAccessibles($relecteur, $brouillon));
        $this->assertSame([EtatHistoire::Brouillon, EtatHistoire::Relecture], $policy->etatsAccessibles($redacteur, $brouillon));
        $this->assertSame([], $policy->etatsAccessibles($redacteur, $relecture));
    }

    public function test_un_redacteur_ne_modifie_pas_une_scene_partagee_avec_l_histoire_d_un_autre(): void
    {
        $redacteurA = User::factory()->create(['role' => UserRole::Redacteur]);
        $redacteurB = User::factory()->create(['role' => UserRole::Redacteur]);
        $relecteur = User::factory()->create(['role' => UserRole::Relecteur]);
        $histoireA = Histoire::factory()->pour($redacteurA)->create(['etat' => EtatHistoire::Brouillon]);
        $histoireB = Histoire::factory()->pour($redacteurB)->create(['etat' => EtatHistoire::Relecture]);
        $scene = Scene::factory()->create();
        $histoireA->scenes()->attach($scene->id, ['est_initiale' => true, 'ordre' => 1]);
        $histoireB->scenes()->attach($scene->id, ['est_initiale' => true, 'ordre' => 1]);

        $this->assertFalse($redacteurA->can('update', $scene));
        $this->assertTrue($relecteur->can('update', $scene));
    }
}
