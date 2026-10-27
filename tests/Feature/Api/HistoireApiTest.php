<?php

namespace Tests\Feature\Api;

use App\Enums\MediaType;
use App\Models\Choix;
use App\Models\Contact;
use App\Models\Histoire;
use App\Models\Media;
use App\Models\Scene;
use App\Models\SousTheme;
use App\Models\Telephone;
use Tests\TestCase;

class HistoireApiTest extends TestCase
{
    public function test_une_histoire_publiee_est_servie_avec_ses_scenes_et_ses_choix(): void
    {
        $histoire = Histoire::factory()->publiee()->create(['ref' => 'histoire_api_test']);
        $media = Media::factory()->create(['libelle' => 'Illustration', 'chemin' => 'medias/image/illustration-api-test.jpg']);
        $intro = Scene::factory()->create(['dialogue_text' => 'Intro', 'media_id' => $media->id]);
        $fin = Scene::factory()->create(['dialogue_text' => 'Fin']);
        $histoire->scenes()->attach($intro->id, ['est_initiale' => true, 'ordre' => 1]);
        $histoire->scenes()->attach($fin->id, ['est_initiale' => false, 'ordre' => 2]);
        Choix::factory()->create(['scene_id' => $intro->id, 'next_scene_id' => $fin->id, 'text_choix' => 'Avancer']);
        Choix::factory()->favorable()->create(['scene_id' => $fin->id, 'text_choix' => 'Rentrer']);

        $response = $this->getJson('/api/histoires/histoire_api_test');

        $response->assertOk()
            ->assertJsonPath('data.ref', 'histoire_api_test')
            ->assertJsonPath('data.titre', $histoire->titre)
            ->assertJsonPath('data.scene_initiale_id', $intro->id)
            ->assertJsonCount(2, 'data.scenes')
            ->assertJsonPath('data.scenes.0.id', $intro->id)
            ->assertJsonPath('data.scenes.0.dialogue_text', 'Intro')
            ->assertJsonPath('data.scenes.0.media.libelle', 'Illustration')
            ->assertJsonPath('data.scenes.0.media.url', $media->url)
            ->assertJsonPath('data.scenes.0.choix.0.text_choix', 'Avancer')
            ->assertJsonPath('data.scenes.0.choix.0.next_scene_id', $fin->id)
            ->assertJsonPath('data.scenes.0.choix.0.issue', null)
            ->assertJsonPath('data.scenes.1.media', null)
            ->assertJsonPath('data.scenes.1.choix.0.next_scene_id', null)
            ->assertJsonPath('data.scenes.1.choix.0.issue', 'favorable');
    }

    public function test_les_scenes_sont_servies_dans_l_ordre_de_la_liaison(): void
    {
        $histoire = Histoire::factory()->publiee()->create(['ref' => 'histoire_ordre_api_test']);
        $seconde = Scene::factory()->create();
        $premiere = Scene::factory()->create();
        $histoire->scenes()->attach($seconde->id, ['est_initiale' => false, 'ordre' => 2]);
        $histoire->scenes()->attach($premiere->id, ['est_initiale' => true, 'ordre' => 1]);

        $this->getJson('/api/histoires/histoire_ordre_api_test')
            ->assertOk()
            ->assertJsonPath('data.scenes.0.id', $premiere->id)
            ->assertJsonPath('data.scenes.1.id', $seconde->id);
    }

    public function test_une_histoire_non_publiee_ou_inconnue_est_introuvable(): void
    {
        Histoire::factory()->create(['ref' => 'histoire_brouillon_api_test']);

        $this->getJson('/api/histoires/histoire_brouillon_api_test')->assertNotFound();
        $this->getJson('/api/histoires/histoire_inconnue_api_test')->assertNotFound();
    }

    public function test_les_champs_de_back_office_ne_sortent_pas(): void
    {
        $histoire = Histoire::factory()->publiee()->create(['ref' => 'histoire_champs_api_test']);
        $scene = Scene::factory()->create(['titre' => 'Titre de back-office']);
        $histoire->scenes()->attach($scene->id, ['est_initiale' => true, 'ordre' => 1]);

        $response = $this->getJson('/api/histoires/histoire_champs_api_test');

        $response->assertOk()
            ->assertJsonMissingPath('data.etat')
            ->assertJsonMissingPath('data.user_id')
            ->assertJsonMissingPath('data.scenes.0.titre')
            ->assertJsonMissingPath('data.scenes.0.ordre');
        $this->assertStringNotContainsString('Titre de back-office', $response->getContent());
    }

    public function test_un_media_inactif_ou_qui_n_est_pas_une_image_ne_sort_pas(): void
    {
        $histoire = Histoire::factory()->publiee()->create(['ref' => 'histoire_media_api_test']);
        $sceneInactif = Scene::factory()->create(['media_id' => Media::factory()->inactif()->create()->id]);
        $sceneVideo = Scene::factory()->create(['media_id' => Media::factory()->type(MediaType::Video)->create()->id]);
        $histoire->scenes()->attach($sceneInactif->id, ['est_initiale' => true, 'ordre' => 1]);
        $histoire->scenes()->attach($sceneVideo->id, ['est_initiale' => false, 'ordre' => 2]);

        $this->getJson('/api/histoires/histoire_media_api_test')
            ->assertOk()
            ->assertJsonPath('data.scenes.0.media', null)
            ->assertJsonPath('data.scenes.1.media', null);
    }

    public function test_un_choix_vers_une_scene_non_rattachee_est_ecarte(): void
    {
        $histoire = Histoire::factory()->publiee()->create(['ref' => 'histoire_orphelin_api_test']);
        $scene = Scene::factory()->create();
        $ailleurs = Scene::factory()->create();
        $histoire->scenes()->attach($scene->id, ['est_initiale' => true, 'ordre' => 1]);
        Choix::factory()->favorable()->create(['scene_id' => $scene->id, 'text_choix' => 'Sortie valide']);
        Choix::factory()->create(['scene_id' => $scene->id, 'next_scene_id' => $ailleurs->id, 'text_choix' => 'Vers une scene orpheline']);

        $response = $this->getJson('/api/histoires/histoire_orphelin_api_test');

        $response->assertOk()
            ->assertJsonCount(1, 'data.scenes.0.choix')
            ->assertJsonPath('data.scenes.0.choix.0.text_choix', 'Sortie valide');
    }

    public function test_un_choix_defavorable_embarque_les_contacts_actifs_du_sous_theme(): void
    {
        $histoire = Histoire::factory()->publiee()->create(['ref' => 'histoire_contacts_api_test']);
        $sousTheme = SousTheme::factory()->create();
        $histoire->sousThemes()->attach($sousTheme->id);

        $actif = Contact::factory()->create();
        $ferme = Contact::factory()->inactif()->create();
        $sousTheme->contacts()->attach($actif->id, ['ordre' => 0]);
        $sousTheme->contacts()->attach($ferme->id, ['ordre' => 1]);
        Telephone::factory()->create(['contact_id' => $actif->id, 'numero' => '11 22 33']);
        Telephone::factory()->inactif()->create(['contact_id' => $actif->id, 'numero' => '99 99 99']);

        $scene = Scene::factory()->create();
        $histoire->scenes()->attach($scene->id, ['est_initiale' => true, 'ordre' => 1]);
        Choix::factory()->defavorable()->create(['scene_id' => $scene->id]);
        Choix::factory()->favorable()->create(['scene_id' => $scene->id]);

        $response = $this->getJson('/api/histoires/histoire_contacts_api_test');

        $response->assertOk()
            ->assertJsonPath('data.scenes.0.choix.0.issue', 'defavorable')
            ->assertJsonCount(1, 'data.scenes.0.choix.0.contacts')
            ->assertJsonPath('data.scenes.0.choix.0.contacts.0.ref', $actif->ref)
            ->assertJsonCount(1, 'data.scenes.0.choix.0.contacts.0.telephones')
            ->assertJsonPath('data.scenes.0.choix.0.contacts.0.telephones.0.numero', '11 22 33')
            ->assertJsonMissingPath('data.scenes.0.choix.1.contacts');
    }

    public function test_la_bifurcation_de_la_scene_remplace_le_sous_theme_de_l_histoire(): void
    {
        $histoire = Histoire::factory()->publiee()->create(['ref' => 'histoire_bifurcation_api_test']);
        $sousThemeHistoire = SousTheme::factory()->create();
        $sousThemeScene = SousTheme::factory()->create();
        $histoire->sousThemes()->attach($sousThemeHistoire->id);

        $contactHistoire = Contact::factory()->create();
        $contactScene = Contact::factory()->create();
        $sousThemeHistoire->contacts()->attach($contactHistoire->id, ['ordre' => 0]);
        $sousThemeScene->contacts()->attach($contactScene->id, ['ordre' => 0]);

        $scene = Scene::factory()->create(['sous_theme_id' => $sousThemeScene->id]);
        $histoire->scenes()->attach($scene->id, ['est_initiale' => true, 'ordre' => 1]);
        Choix::factory()->defavorable()->create(['scene_id' => $scene->id]);

        $this->getJson('/api/histoires/histoire_bifurcation_api_test')
            ->assertOk()
            ->assertJsonCount(1, 'data.scenes.0.choix.0.contacts')
            ->assertJsonPath('data.scenes.0.choix.0.contacts.0.ref', $contactScene->ref);
    }

    public function test_la_fiche_sous_theme_liste_ses_histoires_publiees(): void
    {
        $sousTheme = SousTheme::factory()->create(['ref' => 'sous_theme_histoires_api_test']);
        $publiee = Histoire::factory()->publiee()->create(['ref' => 'histoire_liste_publiee_api_test', 'titre' => 'Histoire publiee']);
        $brouillon = Histoire::factory()->create(['ref' => 'histoire_liste_brouillon_api_test']);
        $sousTheme->histoires()->attach([$publiee->id, $brouillon->id]);

        $this->getJson('/api/sous-themes/sous_theme_histoires_api_test')
            ->assertOk()
            ->assertJsonCount(1, 'data.histoires')
            ->assertJsonPath('data.histoires.0.ref', 'histoire_liste_publiee_api_test')
            ->assertJsonPath('data.histoires.0.titre', 'Histoire publiee');
    }

    public function test_la_fiche_sous_theme_sans_histoire_renvoie_une_liste_vide(): void
    {
        SousTheme::factory()->create(['ref' => 'sous_theme_sans_histoire_api_test']);

        $this->getJson('/api/sous-themes/sous_theme_sans_histoire_api_test')
            ->assertOk()
            ->assertJsonPath('data.histoires', []);
    }
}
