<?php

namespace Tests\Feature\Filament;

use App\Enums\EtatHistoire;
use App\Enums\UserRole;
use App\Filament\Resources\HistoireResource;
use App\Filament\Resources\HistoireResource\Pages\EditHistoire;
use App\Filament\Resources\HistoireResource\Pages\ScenesHistoire;
use App\Models\Choix;
use App\Models\Histoire;
use App\Models\Scene;
use App\Models\SousTheme;
use App\Models\User;
use App\Services\VerificationHistoireService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;
use Tests\TestCase;

class HistoireScenesTest extends TestCase
{
    private User $redacteur;

    protected function setUp(): void
    {
        parent::setUp();

        $this->redacteur = User::factory()->create(['role' => UserRole::Redacteur]);
        $this->actingAs($this->redacteur);
    }

    /**
     * Histoire de trois scenes, dans l'ordre : Intro (depart), Salon, Balcon.
     *
     * @return array{0: Histoire, 1: Scene, 2: Scene, 3: Scene}
     */
    private function histoireATroisScenes(): array
    {
        $histoire = Histoire::factory()->pour($this->redacteur)->create();
        $intro = Scene::factory()->create(['titre' => 'Intro']);
        $salon = Scene::factory()->create(['titre' => 'Salon']);
        $balcon = Scene::factory()->create(['titre' => 'Balcon']);
        $histoire->scenes()->attach($intro->id, ['est_initiale' => true, 'ordre' => 1]);
        $histoire->scenes()->attach($salon->id, ['est_initiale' => false, 'ordre' => 2]);
        $histoire->scenes()->attach($balcon->id, ['est_initiale' => false, 'ordre' => 3]);

        return [$histoire, $intro, $salon, $balcon];
    }

    /**
     * @return list<string>
     */
    private function titresDansLOrdre(Histoire $histoire): array
    {
        return $histoire->scenes()->orderBy('histoire_scene.ordre')->pluck('titre')->all();
    }

    public function test_la_page_presente_chaque_scene_avec_son_entete_ses_badges_et_ses_choix(): void
    {
        [$histoire, $intro, $salon] = $this->histoireATroisScenes();
        Choix::factory()->create(['scene_id' => $intro->id, 'next_scene_id' => $salon->id, 'text_choix' => 'Entrer']);
        Choix::factory()->favorable()->create(['scene_id' => $intro->id, 'text_choix' => 'Appeler une amie']);

        Livewire::test(ScenesHistoire::class, ['record' => $histoire->getKey()])
            ->assertSuccessful()
            ->assertSeeInOrder(['Scène 1 — Intro', 'Scène 2 — Salon', 'Scène 3 — Balcon'])
            ->assertSee('Départ')
            ->assertSee('Choix du joueur')
            ->assertSee('Entrer')
            ->assertSee('→ Scène 2 — Salon')
            ->assertSee('→ Bonne fin')
            ->assertSee('Aucun choix')
            ->assertSee('Jamais atteinte');
    }

    public function test_definir_comme_depart_deplace_la_scene_initiale(): void
    {
        [$histoire, , $salon] = $this->histoireATroisScenes();

        Livewire::test(ScenesHistoire::class, ['record' => $histoire->getKey()])
            ->call('definirDepart', $salon->id);

        $this->assertSame($salon->id, $histoire->sceneInitiale()?->id);
    }

    public function test_monter_et_descendre_changent_l_ordre_des_scenes(): void
    {
        [$histoire, $intro, , $balcon] = $this->histoireATroisScenes();

        $page = Livewire::test(ScenesHistoire::class, ['record' => $histoire->getKey()])
            ->call('monter', $balcon->id);

        $this->assertSame(['Intro', 'Balcon', 'Salon'], $this->titresDansLOrdre($histoire));

        $page->call('descendre', $intro->id);

        $this->assertSame(['Balcon', 'Intro', 'Salon'], $this->titresDansLOrdre($histoire));
    }

    public function test_retirer_une_scene_la_detache_sans_la_supprimer(): void
    {
        [$histoire, , $salon] = $this->histoireATroisScenes();

        Livewire::test(ScenesHistoire::class, ['record' => $histoire->getKey()])
            ->callAction('retirerScene', arguments: ['scene' => $salon->id]);

        $this->assertSame(['Intro', 'Balcon'], $this->titresDansLOrdre($histoire));
        $this->assertModelExists($salon);
    }

    public function test_supprimer_une_scene_la_supprime_definitivement(): void
    {
        [$histoire, , $salon] = $this->histoireATroisScenes();

        Livewire::test(ScenesHistoire::class, ['record' => $histoire->getKey()])
            ->callAction('supprimerScene', arguments: ['scene' => $salon->id]);

        $this->assertModelMissing($salon);
    }

    public function test_une_scene_d_une_autre_histoire_est_reutilisee_en_fin_de_liste(): void
    {
        [$histoire] = $this->histoireATroisScenes();
        $autre = Histoire::factory()->pour($this->redacteur)->create();
        $partagee = Scene::factory()->create(['titre' => 'Retour en bus']);
        $autre->scenes()->attach($partagee->id, ['est_initiale' => true, 'ordre' => 1]);

        Livewire::test(ScenesHistoire::class, ['record' => $histoire->getKey()])
            ->set('reutilisationOuverte', true)
            ->assertSee('Retour en bus')
            ->set('sceneAReutiliser', $partagee->id)
            ->call('reutiliser')
            ->assertSet('reutilisationOuverte', false);

        $this->assertSame(['Intro', 'Salon', 'Balcon', 'Retour en bus'], $this->titresDansLOrdre($histoire));
    }

    public function test_une_scene_d_une_autre_histoire_ne_peut_pas_agir_sur_cette_page(): void
    {
        [$histoire] = $this->histoireATroisScenes();
        $etrangere = Scene::factory()->create();

        // Hors d'un test, l'exception devient une reponse 404.
        $this->expectException(ModelNotFoundException::class);

        Livewire::test(ScenesHistoire::class, ['record' => $histoire->getKey()])
            ->call('definirDepart', $etrangere->id);
    }

    public function test_une_histoire_verrouillee_est_en_lecture_seule(): void
    {
        $histoire = Histoire::factory()->pour($this->redacteur)->create(['etat' => EtatHistoire::Relecture]);
        $scene = Scene::factory()->create(['titre' => 'Intro']);
        $histoire->scenes()->attach($scene->id, ['est_initiale' => true, 'ordre' => 1]);

        Livewire::test(ScenesHistoire::class, ['record' => $histoire->getKey()])
            ->assertSuccessful()
            ->assertDontSeeHtml('wire:click="nouvelleScene"')
            ->assertDontSeeHtml('wire:click="modifier(')
            ->assertSee('Lecture seule');
    }

    public function test_une_action_d_ecriture_est_refusee_sur_une_histoire_verrouillee(): void
    {
        $histoire = Histoire::factory()->pour($this->redacteur)->create(['etat' => EtatHistoire::Relecture]);
        $scene = Scene::factory()->create(['titre' => 'Intro']);
        $histoire->scenes()->attach($scene->id, ['est_initiale' => true, 'ordre' => 1]);

        Livewire::test(ScenesHistoire::class, ['record' => $histoire->getKey()])
            ->call('nouvelleScene')
            ->assertForbidden();
    }

    public function test_les_scenes_d_un_autre_auteur_ne_sont_pas_proposees(): void
    {
        [$histoire] = $this->histoireATroisScenes();
        $autre = Histoire::factory()->pour($this->redacteur)->create();
        $histoireDeB = Histoire::factory()->pour(User::factory()->create(['role' => UserRole::Redacteur]))->create();
        $sceneDeB = Scene::factory()->create(['titre' => 'Scène de B']);
        $histoireDeB->scenes()->attach($sceneDeB->id, ['est_initiale' => true, 'ordre' => 1]);
        $autre->scenes()->attach($sceneDeB->id, ['est_initiale' => false, 'ordre' => 1]);

        Livewire::test(ScenesHistoire::class, ['record' => $histoire->getKey()])
            ->set('reutilisationOuverte', true)
            ->assertDontSee('Scène de B')
            ->set('sceneAReutiliser', $sceneDeB->id)
            ->call('reutiliser');

        $this->assertFalse($histoire->scenes()->whereKey($sceneDeB->id)->exists());
    }

    public function test_un_relecteur_reutilise_la_scene_d_un_autre_auteur(): void
    {
        [$histoire] = $this->histoireATroisScenes();
        $autre = Histoire::factory()->pour($this->redacteur)->create();
        $histoireDeB = Histoire::factory()->pour(User::factory()->create(['role' => UserRole::Redacteur]))->create();
        $sceneDeB = Scene::factory()->create(['titre' => 'Scène de B']);
        $histoireDeB->scenes()->attach($sceneDeB->id, ['est_initiale' => true, 'ordre' => 1]);
        $autre->scenes()->attach($sceneDeB->id, ['est_initiale' => false, 'ordre' => 1]);
        $this->actingAs(User::factory()->create(['role' => UserRole::Relecteur]));

        Livewire::test(ScenesHistoire::class, ['record' => $histoire->getKey()])
            ->set('reutilisationOuverte', true)
            ->assertSee('Scène de B');
    }

    public function test_modifier_une_scene_partagee_avec_une_histoire_en_relecture_est_refuse(): void
    {
        [$histoire, , $salon] = $this->histoireATroisScenes();
        $histoireDeB = Histoire::factory()->pour(User::factory()->create(['role' => UserRole::Redacteur]))->create(['etat' => EtatHistoire::Relecture]);
        $histoireDeB->scenes()->attach($salon->id, ['est_initiale' => true, 'ordre' => 1]);

        Livewire::test(ScenesHistoire::class, ['record' => $histoire->getKey()])
            ->call('modifier', $salon->id)
            ->assertForbidden();
    }

    public function test_une_scene_partagee_avec_une_histoire_verrouillee_n_est_pas_modifiable(): void
    {
        [$histoire, $intro, $salon] = $this->histoireATroisScenes();
        $histoireDeB = Histoire::factory()->pour(User::factory()->create(['role' => UserRole::Redacteur]))->create(['etat' => EtatHistoire::Relecture]);
        $histoireDeB->scenes()->attach($salon->id, ['est_initiale' => true, 'ordre' => 1]);

        $sections = collect(Livewire::test(ScenesHistoire::class, ['record' => $histoire->getKey()])->instance()->getSections())
            ->keyBy(fn (array $section) => $section['scene']->id);

        $this->assertTrue($sections[$intro->id]['modifiable']);
        $this->assertFalse($sections[$salon->id]['modifiable']);
    }

    public function test_une_scene_partagee_ne_peut_pas_etre_supprimee(): void
    {
        [$histoire, , $salon] = $this->histoireATroisScenes();
        $autre = Histoire::factory()->pour($this->redacteur)->create();
        $autre->scenes()->attach($salon->id, ['est_initiale' => false, 'ordre' => 1]);

        Livewire::test(ScenesHistoire::class, ['record' => $histoire->getKey()])
            ->callAction('supprimerScene', arguments: ['scene' => $salon->id]);

        $this->assertModelExists($salon);
    }

    public function test_une_seule_scene_en_modification_a_la_fois(): void
    {
        [$histoire, $intro] = $this->histoireATroisScenes();

        Livewire::test(ScenesHistoire::class, ['record' => $histoire->getKey()])
            ->call('modifier', $intro->id)
            ->assertSet('sceneEnEdition', $intro->id)
            ->assertSee('Modification en cours')
            ->call('annuler')
            ->assertSet('sceneEnEdition', null);
    }

    public function test_la_page_scenes_s_affiche_en_navigation_reelle(): void
    {
        [$histoire] = $this->histoireATroisScenes();

        $this->get(HistoireResource::getUrl('scenes', ['record' => $histoire]))
            ->assertOk()
            ->assertSeeInOrder(['2. Scènes', 'Nouvelle scène', 'Scène 1 — Intro']);
    }

    public function test_modifier_remplit_le_formulaire_de_la_scene(): void
    {
        [$histoire, $intro, $salon] = $this->histoireATroisScenes();
        $choix = Choix::factory()->create(['scene_id' => $intro->id, 'next_scene_id' => $salon->id, 'text_choix' => 'Entrer']);

        $page = Livewire::test(ScenesHistoire::class, ['record' => $histoire->getKey()])
            ->call('modifier', $intro->id)
            ->assertFormSet(['titre' => 'Intro'])
            ->assertSee('Enregistrer la scène')
            ->assertSee('Média et thème');

        // Le repeater renumerote ses lignes (cles UUID) : on compare le contenu.
        $lignes = array_values($page->get('donnees.choix'));
        $this->assertCount(1, $lignes);
        $this->assertSame($choix->id, $lignes[0]['id']);
        $this->assertSame('Entrer', $lignes[0]['text_choix']);
        $this->assertSame('continuer', $lignes[0]['suite']);
        $this->assertSame($salon->id, $lignes[0]['next_scene_id']);
    }

    public function test_enregistrer_met_a_jour_la_scene_et_synchronise_ses_choix(): void
    {
        [$histoire, $intro, $salon, $balcon] = $this->histoireATroisScenes();
        $garde = Choix::factory()->create(['scene_id' => $intro->id, 'next_scene_id' => $salon->id, 'text_choix' => 'Entrer']);
        $retire = Choix::factory()->favorable()->create(['scene_id' => $intro->id, 'text_choix' => 'Rentrer']);

        Livewire::test(ScenesHistoire::class, ['record' => $histoire->getKey()])
            ->call('modifier', $intro->id)
            ->set('donnees.choix', [
                "choix-{$garde->id}" => ['id' => $garde->id, 'text_choix' => 'Entrer vite', 'suite' => 'continuer', 'next_scene_id' => $balcon->id],
                'nouveau' => ['id' => null, 'text_choix' => 'Appeler une amie', 'suite' => 'defavorable'],
            ])
            ->fillForm(['titre' => 'Arrivée', 'dialogue_text' => 'Tu arrives à une soirée.'])
            ->call('enregistrer')
            ->assertHasNoFormErrors()
            ->assertSet('sceneEnEdition', null);

        $intro->refresh();
        $this->assertSame('Arrivée', $intro->titre);
        $this->assertSame('Tu arrives à une soirée.', $intro->dialogue_text);
        $this->assertModelMissing($retire);
        $this->assertSame($balcon->id, $garde->fresh()->next_scene_id);
        $this->assertSame('Entrer vite', $garde->fresh()->text_choix);
        $this->assertDatabaseHas('choix', ['scene_id' => $intro->id, 'text_choix' => 'Appeler une amie', 'issue' => 'defavorable', 'next_scene_id' => null]);
    }

    public function test_un_choix_ne_propose_pas_de_creer_une_scene(): void
    {
        [$histoire, $intro] = $this->histoireATroisScenes();

        Livewire::test(ScenesHistoire::class, ['record' => $histoire->getKey()])
            ->call('modifier', $intro->id)
            ->set('donnees.choix', ['a' => ['id' => null, 'text_choix' => 'Danser', 'suite' => 'continuer', 'next_scene_id' => null]])
            ->assertDontSee('+ Nouvelle scène…')
            ->assertDontSee('Nom de la nouvelle scène');
    }

    public function test_continuer_sans_scene_visee_est_enregistre_et_signale(): void
    {
        [$histoire, $intro] = $this->histoireATroisScenes();

        Livewire::test(ScenesHistoire::class, ['record' => $histoire->getKey()])
            ->call('modifier', $intro->id)
            ->set('donnees.choix', [
                'a' => ['id' => null, 'text_choix' => 'Danser', 'suite' => 'continuer', 'next_scene_id' => null],
            ])
            ->call('enregistrer')
            ->assertHasNoFormErrors()
            ->assertSee('→ Suite à choisir')
            ->assertSee('Choix à compléter');

        $this->assertDatabaseHas('choix', ['scene_id' => $intro->id, 'text_choix' => 'Danser', 'next_scene_id' => null, 'issue' => null]);
    }

    public function test_la_page_explique_la_methode_scenes_puis_choix(): void
    {
        [$histoire] = $this->histoireATroisScenes();

        Livewire::test(ScenesHistoire::class, ['record' => $histoire->getKey()])
            ->assertSee('Comment écrire ton histoire')
            ->assertSee('Les scènes d\'abord.', false)
            ->assertSee('Puis les choix.');
    }

    public function test_les_choix_en_attente_de_scene_suivante_sont_rappeles(): void
    {
        [$histoire, $intro] = $this->histoireATroisScenes();
        Choix::factory()->create(['scene_id' => $intro->id, 'next_scene_id' => null, 'issue' => null, 'text_choix' => 'Danser']);

        Livewire::test(ScenesHistoire::class, ['record' => $histoire->getKey()])
            ->assertSee('1 choix attend encore sa scène suivante')
            ->assertSee('Scène 1 — Intro : « Danser »')
            ->assertSee('Relier ce choix')
            ->call('nouvelleScene')
            ->assertDontSee('Relier ce choix')
            ->fillForm(['titre' => 'La piste', 'dialogue_text' => 'La musique est forte.'])
            ->call('enregistrer')
            ->assertNotified('« La piste » est enregistrée.');
    }

    public function test_un_choix_ne_peut_pas_viser_une_scene_d_une_autre_histoire(): void
    {
        [$histoire, $intro] = $this->histoireATroisScenes();
        $etrangere = Scene::factory()->create();

        // Valeur modifiee dans le navigateur : absente des options proposees.
        Livewire::test(ScenesHistoire::class, ['record' => $histoire->getKey()])
            ->call('modifier', $intro->id)
            ->set('donnees.choix', ['a' => ['id' => null, 'text_choix' => 'Fuir', 'suite' => 'continuer', 'next_scene_id' => $etrangere->id]])
            ->call('enregistrer')
            ->assertHasFormErrors(['choix.a.next_scene_id' => 'in']);

        $this->assertDatabaseMissing('choix', ['text_choix' => 'Fuir']);
    }

    public function test_une_suite_inventee_est_refusee(): void
    {
        [$histoire, $intro] = $this->histoireATroisScenes();

        Livewire::test(ScenesHistoire::class, ['record' => $histoire->getKey()])
            ->call('modifier', $intro->id)
            ->set('donnees.choix', ['a' => ['id' => null, 'text_choix' => 'Fuir', 'suite' => 'pirate']])
            ->call('enregistrer')
            ->assertHasFormErrors(['choix.a.suite' => 'in']);
    }

    public function test_une_nouvelle_scene_d_une_histoire_vide_devient_le_depart(): void
    {
        $histoire = Histoire::factory()->pour($this->redacteur)->create();

        Livewire::test(ScenesHistoire::class, ['record' => $histoire->getKey()])
            ->call('nouvelleScene')
            ->assertSet('sceneEnEdition', 0)
            ->assertSee('Nouvelle scène')
            ->fillForm(['titre' => 'Arrivée', 'dialogue_text' => 'Tu arrives à une soirée.'])
            ->call('enregistrer')
            ->assertHasNoFormErrors();

        $this->assertSame('Arrivée', $histoire->sceneInitiale()?->titre);
    }

    public function test_annuler_referme_la_scene_sans_rien_enregistrer(): void
    {
        [$histoire, $intro] = $this->histoireATroisScenes();

        Livewire::test(ScenesHistoire::class, ['record' => $histoire->getKey()])
            ->call('modifier', $intro->id)
            ->fillForm(['titre' => 'Brouillon jeté'])
            ->call('annuler')
            ->assertSet('sceneEnEdition', null);

        $this->assertSame('Intro', $intro->fresh()->titre);
    }

    public function test_le_dialogue_d_une_scene_est_limite(): void
    {
        [$histoire, $intro] = $this->histoireATroisScenes();
        $trop = str_repeat('a', HistoireResource::DIALOGUE_MAX + 1);

        Livewire::test(ScenesHistoire::class, ['record' => $histoire->getKey()])
            ->call('modifier', $intro->id)
            ->fillForm(['titre' => 'Intro', 'dialogue_text' => $trop])
            ->call('enregistrer')
            ->assertHasFormErrors(['dialogue_text' => 'max']);

        $this->assertNotSame($trop, $intro->fresh()->dialogue_text);
    }

    public function test_le_dialogue_d_une_scene_accepte_exactement_la_limite(): void
    {
        [$histoire, $intro] = $this->histoireATroisScenes();
        $limite = str_repeat('a', HistoireResource::DIALOGUE_MAX);

        Livewire::test(ScenesHistoire::class, ['record' => $histoire->getKey()])
            ->call('modifier', $intro->id)
            ->fillForm(['titre' => 'Intro', 'dialogue_text' => $limite])
            ->call('enregistrer')
            ->assertHasNoFormErrors();

        $this->assertSame($limite, $intro->fresh()->dialogue_text);
    }

    public function test_une_histoire_complete_passe_tous_les_controles(): void
    {
        $histoire = Histoire::factory()->pour($this->redacteur)->create();
        $histoire->sousThemes()->attach(SousTheme::factory()->create());
        $intro = Scene::factory()->create();
        $fin = Scene::factory()->create();
        $histoire->scenes()->attach($intro->id, ['est_initiale' => true, 'ordre' => 1]);
        $histoire->scenes()->attach($fin->id, ['est_initiale' => false, 'ordre' => 2]);
        Choix::factory()->create(['scene_id' => $intro->id, 'next_scene_id' => $fin->id]);
        Choix::factory()->defavorable()->create(['scene_id' => $fin->id]);

        $this->assertSame([], app(VerificationHistoireService::class)->problemes($histoire));
    }

    public function test_les_controles_signalent_ce_qui_manque(): void
    {
        $histoire = Histoire::factory()->pour($this->redacteur)->create();
        $intro = Scene::factory()->create();
        $orpheline = Scene::factory()->create(['titre' => 'Orpheline']);
        $ailleurs = Scene::factory()->create();
        $histoire->scenes()->attach($intro->id, ['est_initiale' => true, 'ordre' => 1]);
        $histoire->scenes()->attach($orpheline->id, ['est_initiale' => false, 'ordre' => 2]);
        Choix::factory()->create(['scene_id' => $intro->id, 'next_scene_id' => $ailleurs->id]);
        Choix::factory()->create(['scene_id' => $intro->id, 'next_scene_id' => null, 'issue' => null]);

        $controles = collect(app(VerificationHistoireService::class)->controler($histoire))->keyBy('libelle');

        $this->assertTrue($controles['L\'histoire a une scène de départ']['ok']);
        $this->assertSame(['Orpheline'], $controles['Toutes les scènes sont atteignables depuis le départ']['details']);
        $this->assertFalse($controles['Chaque scène propose au moins un choix']['ok']);
        $this->assertFalse($controles['Chaque choix mène à une scène ou à une fin']['ok']);
        $this->assertFalse($controles['Les choix ne mènent qu\'à des scènes de cette histoire']['ok']);
        $this->assertFalse($controles['Le parcours a au moins une fin']['ok']);
        $this->assertFalse($controles['L\'histoire est rattachée à au moins un sous-thème']['ok']);
    }

    public function test_une_scene_sans_mise_en_situation_est_signalee(): void
    {
        $histoire = Histoire::factory()->pour($this->redacteur)->create();
        $vide = Scene::factory()->create(['titre' => 'À écrire', 'dialogue_text' => '']);
        $histoire->scenes()->attach($vide->id, ['est_initiale' => true, 'ordre' => 1]);

        $controles = collect(app(VerificationHistoireService::class)->controler($histoire))->keyBy('cle');

        $this->assertFalse($controles['mise_en_situation']['ok']);
        $this->assertSame(['À écrire'], $controles['mise_en_situation']['details']);
    }

    public function test_enregistrer_une_histoire_incomplete_hors_brouillon_avertit_sans_bloquer(): void
    {
        $histoire = Histoire::factory()->pour($this->redacteur)->create(['etat' => EtatHistoire::Brouillon]);

        Livewire::test(EditHistoire::class, ['record' => $histoire->getKey()])
            ->fillForm(['etat' => EtatHistoire::Relecture->value])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified('Enregistrée, mais l\'histoire semble incomplète');

        $this->assertSame(EtatHistoire::Relecture, $histoire->fresh()->etat);
    }

    public function test_le_lien_verifier_ouvre_la_modale_avec_un_lien_pour_corriger(): void
    {
        $histoire = Histoire::factory()->pour($this->redacteur)->create();

        Livewire::test(ScenesHistoire::class, ['record' => $histoire->getKey()])
            ->dispatch('ouvrir-verification')
            ->assertActionMounted('verifier')
            ->assertSee('L\'histoire a une scène de départ')
            ->assertSee('Corriger')
            ->assertSee(HistoireResource::getUrl('scenes', ['record' => $histoire]));
    }

    public function test_verifier_apparait_dans_le_menu_dans_une_histoire(): void
    {
        $histoire = Histoire::factory()->pour($this->redacteur)->create();

        $this->get(HistoireResource::getUrl('edit', ['record' => $histoire]))
            ->assertOk()
            ->assertSee('#verifier')
            ->assertSeeInOrder(['fi-header', '1. Infos de l&#039;histoire', '2. Scènes', '3. Prévisualiser'], false);
    }

    /**
     * Test separe du precedent : Filament garde le menu construit d'une
     * requete a l'autre au sein d'une meme instance d'application.
     */
    public function test_verifier_n_apparait_pas_dans_le_menu_hors_d_une_histoire(): void
    {
        $this->get(HistoireResource::getUrl('index'))
            ->assertOk()
            ->assertDontSee('#verifier');
    }
}
