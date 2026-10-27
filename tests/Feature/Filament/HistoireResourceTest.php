<?php

namespace Tests\Feature\Filament;

use App\Enums\EtatHistoire;
use App\Enums\UserRole;
use App\Filament\Resources\HistoireResource\Pages\CreateHistoire;
use App\Filament\Resources\HistoireResource\Pages\EditHistoire;
use App\Filament\Resources\HistoireResource\Pages\ListHistoires;
use App\Models\Histoire;
use App\Models\SousTheme;
use App\Models\User;
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
        $histoire = Histoire::factory()->create(['ref' => 'histoire_test', 'titre' => 'Histoire test']);

        Livewire::test(EditHistoire::class, ['record' => $histoire->getKey()])
            ->fillForm(['titre' => 'Histoire test (modifiée)'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('histoire_test', $histoire->fresh()->ref);
    }

    public function test_l_auteur_est_affiche_dans_la_liste_et_dans_les_infos(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Redacteur]));
        $auteur = User::factory()->create(['role' => UserRole::Redacteur, 'prenom' => 'Camille', 'nom' => 'Martin']);
        $histoire = Histoire::factory()->create(['user_id' => $auteur->id]);

        Livewire::test(ListHistoires::class)->assertSee('Camille Martin');
        Livewire::test(EditHistoire::class, ['record' => $histoire->getKey()])->assertSee('Camille Martin');
    }
}
