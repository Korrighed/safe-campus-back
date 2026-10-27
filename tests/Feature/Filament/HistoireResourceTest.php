<?php

namespace Tests\Feature\Filament;

use App\Enums\EtatHistoire;
use App\Enums\UserRole;
use App\Filament\Resources\HistoireResource;
use App\Filament\Resources\HistoireResource\Pages\CreateHistoire;
use App\Filament\Resources\HistoireResource\Pages\EditHistoire;
use App\Filament\Resources\HistoireResource\Pages\ListHistoires;
use App\Models\Histoire;
use App\Models\SousTheme;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;
use Tests\TestCase;

class HistoireResourceTest extends TestCase
{
    public function test_un_webmaster_est_refuse_sur_la_liste(): void
    {
        // Inverse de ContactResource/ThemeResource — voir docs/schema_bd.md § Rôles.
        $this->actingAs(User::factory()->create(['role' => UserRole::Webmaster]));

        Livewire::test(ListHistoires::class)->assertForbidden();
    }

    public function test_un_compte_sans_role_est_refuse_sur_la_liste(): void
    {
        $this->actingAs(User::factory()->create(['role' => null]));

        Livewire::test(ListHistoires::class)->assertForbidden();
    }

    public function test_un_redacteur_accede_a_la_liste(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Redacteur]));

        Livewire::test(ListHistoires::class)->assertSuccessful();
    }

    public function test_un_redacteur_cree_une_histoire_avec_sa_premiere_scene(): void
    {
        $redacteur = User::factory()->create(['role' => UserRole::Redacteur]);
        $this->actingAs($redacteur);
        $sousTheme = SousTheme::factory()->create();

        Livewire::test(CreateHistoire::class)
            ->fillForm([
                'titre' => 'Une soirée qui dérape',
                'sousThemes' => [$sousTheme->id],
                'premiere_scene' => [
                    'titre' => 'Arrivée',
                    'dialogue_text' => 'Tu arrives à une soirée.',
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $histoire = Histoire::where('ref', 'une_soiree_qui_derape')->firstOrFail();

        $this->assertSame($redacteur->id, $histoire->user_id);
        $this->assertSame(EtatHistoire::Brouillon, $histoire->etat);
        $this->assertSame('Arrivée', $histoire->sceneInitiale()?->titre);
        $this->assertTrue($histoire->sousThemes->contains($sousTheme));
    }

    public function test_la_creation_exige_le_dialogue_de_la_premiere_scene(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Redacteur]));

        Livewire::test(CreateHistoire::class)
            ->fillForm([
                'titre' => 'Sans dialogue',
                'premiere_scene' => ['titre' => 'Arrivée', 'dialogue_text' => ''],
            ])
            ->call('create')
            ->assertHasFormErrors(['premiere_scene.dialogue_text' => 'required']);
    }

    public function test_le_ref_reste_inchange_a_l_edition(): void
    {
        $redacteur = User::factory()->create(['role' => UserRole::Redacteur]);
        $this->actingAs($redacteur);
        $histoire = Histoire::factory()->pour($redacteur)->create(['ref' => 'histoire_test', 'titre' => 'Histoire test']);

        Livewire::test(EditHistoire::class, ['record' => $histoire->getKey()])
            ->fillForm(['titre' => 'Histoire test (modifiée)'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('histoire_test', $histoire->fresh()->ref);
    }

    public function test_l_auteur_est_affiche_dans_la_liste_et_dans_les_infos(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Relecteur]));
        $auteur = User::factory()->create(['role' => UserRole::Redacteur, 'prenom' => 'Camille', 'nom' => 'Martin']);
        $histoire = Histoire::factory()->create(['user_id' => $auteur->id]);

        Livewire::test(ListHistoires::class)->assertSee('Camille Martin');
        Livewire::test(EditHistoire::class, ['record' => $histoire->getKey()])->assertSee('Camille Martin');
    }

    public function test_un_redacteur_ne_voit_que_ses_histoires(): void
    {
        $redacteur = User::factory()->create(['role' => UserRole::Redacteur]);
        $this->actingAs($redacteur);
        $sienne = Histoire::factory()->pour($redacteur)->create();
        $autre = Histoire::factory()->pour(User::factory()->create(['role' => UserRole::Redacteur]))->create();

        Livewire::test(ListHistoires::class)
            ->assertCanSeeTableRecords([$sienne])
            ->assertCanNotSeeTableRecords([$autre]);
    }

    public function test_un_redacteur_ne_peut_pas_ouvrir_l_histoire_d_un_autre(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Redacteur]));
        $autre = Histoire::factory()->pour(User::factory()->create(['role' => UserRole::Redacteur]))->create();

        $this->expectException(ModelNotFoundException::class);

        Livewire::test(EditHistoire::class, ['record' => $autre->getKey()]);
    }

    public function test_un_redacteur_ne_peut_pas_modifier_une_histoire_en_relecture(): void
    {
        $redacteur = User::factory()->create(['role' => UserRole::Redacteur]);
        $this->actingAs($redacteur);
        $histoire = Histoire::factory()->pour($redacteur)->create(['etat' => EtatHistoire::Relecture]);

        Livewire::test(EditHistoire::class, ['record' => $histoire->getKey()])->assertForbidden();
    }

    public function test_un_relecteur_voit_toutes_les_histoires(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Relecteur]));
        $premiere = Histoire::factory()->pour(User::factory()->create(['role' => UserRole::Redacteur]))->create();
        $seconde = Histoire::factory()->pour(User::factory()->create(['role' => UserRole::Redacteur]))->create();

        Livewire::test(ListHistoires::class)->assertCanSeeTableRecords([$premiere, $seconde]);
    }

    public function test_un_relecteur_ne_peut_pas_creer(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Relecteur]));

        Livewire::test(CreateHistoire::class)->assertForbidden();
        Livewire::test(ListHistoires::class)->assertActionHidden('create');
    }

    public function test_la_suppression_groupee_ignore_les_histoires_verrouillees(): void
    {
        $redacteur = User::factory()->create(['role' => UserRole::Redacteur]);
        $this->actingAs($redacteur);
        $libre = Histoire::factory()->pour($redacteur)->create(['etat' => EtatHistoire::Brouillon]);
        $verrouillee = Histoire::factory()->pour($redacteur)->create(['etat' => EtatHistoire::Relecture]);

        Livewire::test(ListHistoires::class)
            ->callTableBulkAction('supprimer', [$libre, $verrouillee]);

        $this->assertModelMissing($libre);
        $this->assertModelExists($verrouillee);
    }

    public function test_un_redacteur_n_a_que_brouillon_et_relecture(): void
    {
        $redacteur = User::factory()->create(['role' => UserRole::Redacteur]);
        $this->actingAs($redacteur);
        $histoire = Histoire::factory()->pour($redacteur)->create(['etat' => EtatHistoire::Brouillon]);

        $this->assertSame(['brouillon', 'relecture'], array_keys(HistoireResource::optionsEtat($histoire)));
    }

    public function test_un_redacteur_soumet_son_histoire_en_relecture(): void
    {
        $redacteur = User::factory()->create(['role' => UserRole::Redacteur]);
        $this->actingAs($redacteur);
        $histoire = Histoire::factory()->pour($redacteur)->create(['etat' => EtatHistoire::Brouillon]);

        Livewire::test(EditHistoire::class, ['record' => $histoire->getKey()])
            ->fillForm(['etat' => 'relecture'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(EtatHistoire::Relecture, $histoire->fresh()->etat);
    }

    public function test_un_redacteur_ne_peut_pas_forcer_la_publication(): void
    {
        $redacteur = User::factory()->create(['role' => UserRole::Redacteur]);
        $this->actingAs($redacteur);
        $histoire = Histoire::factory()->pour($redacteur)->create(['etat' => EtatHistoire::Brouillon]);

        Livewire::test(EditHistoire::class, ['record' => $histoire->getKey()])
            ->fillForm(['etat' => 'publie'])
            ->call('save')
            ->assertHasFormErrors(['etat']);

        Livewire::test(EditHistoire::class, ['record' => $histoire->getKey()])
            ->fillForm(['etat' => 'valide'])
            ->call('save')
            ->assertHasFormErrors(['etat']);

        $this->assertSame(EtatHistoire::Brouillon, $histoire->fresh()->etat);
    }

    public function test_un_relecteur_fait_passer_l_histoire_par_tous_les_etats(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Relecteur]));
        $histoire = Histoire::factory()
            ->pour(User::factory()->create(['role' => UserRole::Redacteur]))
            ->create(['etat' => EtatHistoire::Relecture]);

        Livewire::test(EditHistoire::class, ['record' => $histoire->getKey()])
            ->fillForm(['etat' => 'valide'])
            ->call('save')
            ->assertHasNoFormErrors()
            ->fillForm(['etat' => 'publie'])
            ->call('save')
            ->assertHasNoFormErrors()
            ->fillForm(['etat' => 'brouillon'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(EtatHistoire::Brouillon, $histoire->fresh()->etat);
    }
}
