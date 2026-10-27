<?php

namespace Tests\Feature\Filament;

use App\Enums\TelephoneType;
use App\Enums\UserRole;
use App\Filament\Resources\HistoireResource\Pages\PreviewHistoire;
use App\Models\Choix;
use App\Models\Contact;
use App\Models\Histoire;
use App\Models\Media;
use App\Models\Scene;
use App\Models\SousTheme;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;
use Tests\TestCase;

class HistoirePreviewTest extends TestCase
{
    public function test_la_preview_affiche_la_scene_initiale(): void
    {
        $redacteur = User::factory()->create(['role' => UserRole::Redacteur]);
        $this->actingAs($redacteur);

        $histoire = Histoire::factory()->pour($redacteur)->create();
        $scene = Scene::factory()->create(['dialogue_text' => 'Tu arrives à une soirée.']);
        $histoire->scenes()->attach($scene->id, ['est_initiale' => true]);
        Choix::factory()->create(['scene_id' => $scene->id, 'text_choix' => 'Entrer']);

        Livewire::test(PreviewHistoire::class, ['record' => $histoire->getKey()])
            ->assertSuccessful()
            ->assertSee('Tu arrives à une soirée.')
            ->assertSee('Entrer');
    }

    public function test_la_preview_affiche_l_image_de_la_scene(): void
    {
        $redacteur = User::factory()->create(['role' => UserRole::Redacteur]);
        $this->actingAs($redacteur);

        $histoire = Histoire::factory()->pour($redacteur)->create();
        $media = Media::factory()->create(['libelle' => 'Illustration de test', 'chemin' => 'medias/image/illustration-test.jpg']);
        $scene = Scene::factory()->create(['media_id' => $media->id]);
        $histoire->scenes()->attach($scene->id, ['est_initiale' => true]);

        Livewire::test(PreviewHistoire::class, ['record' => $histoire->getKey()])
            ->assertSeeHtml('src="'.$media->url.'"')
            ->assertSeeHtml('alt="Illustration de test"');
    }

    public function test_cliquer_un_choix_avance_a_la_scene_suivante(): void
    {
        $redacteur = User::factory()->create(['role' => UserRole::Redacteur]);
        $this->actingAs($redacteur);

        $histoire = Histoire::factory()->pour($redacteur)->create();
        $scene1 = Scene::factory()->create(['dialogue_text' => 'Scène 1']);
        $scene2 = Scene::factory()->create(['dialogue_text' => 'Scène 2']);
        $histoire->scenes()->attach($scene1->id, ['est_initiale' => true]);
        $histoire->scenes()->attach($scene2->id, ['est_initiale' => false]);
        $choix = Choix::factory()->create([
            'scene_id' => $scene1->id,
            'next_scene_id' => $scene2->id,
            'text_choix' => 'Avancer',
        ]);

        Livewire::test(PreviewHistoire::class, ['record' => $histoire->getKey()])
            ->call('choisir', $choix->id)
            ->assertSee('Scène 2');
    }

    public function test_un_choix_favorable_affiche_la_fin_sans_contact(): void
    {
        $redacteur = User::factory()->create(['role' => UserRole::Redacteur]);
        $this->actingAs($redacteur);

        $histoire = Histoire::factory()->pour($redacteur)->create();
        $scene = Scene::factory()->create();
        $histoire->scenes()->attach($scene->id, ['est_initiale' => true]);
        $choix = Choix::factory()->favorable()->create([
            'scene_id' => $scene->id,
            'text_choix' => 'Bonne conduite',
        ]);

        Livewire::test(PreviewHistoire::class, ['record' => $histoire->getKey()])
            ->call('choisir', $choix->id)
            ->assertSee('Bonne fin')
            ->assertDontSee('Contacts proposés');
    }

    public function test_un_choix_defavorable_resout_les_contacts_du_sous_theme(): void
    {
        $redacteur = User::factory()->create(['role' => UserRole::Redacteur]);
        $this->actingAs($redacteur);

        $sousTheme = SousTheme::factory()->create();
        $contact = Contact::factory()->create(['nom' => 'SOS Écoute']);
        $contact->sousThemes()->attach($sousTheme->id, ['ordre' => 0]);
        $contact->telephones()->create(['numero' => '05 05', 'type' => TelephoneType::Urgence]);

        $histoire = Histoire::factory()->pour($redacteur)->create();
        $histoire->sousThemes()->attach($sousTheme->id);
        $scene = Scene::factory()->create();
        $histoire->scenes()->attach($scene->id, ['est_initiale' => true]);
        $choix = Choix::factory()->defavorable()->create([
            'scene_id' => $scene->id,
            'text_choix' => 'Mauvaise conduite',
        ]);

        Livewire::test(PreviewHistoire::class, ['record' => $histoire->getKey()])
            ->call('choisir', $choix->id)
            ->assertSee('Fin à risque')
            ->assertSee('SOS Écoute')
            ->assertSee('05 05');
    }

    public function test_recommencer_revient_a_la_scene_initiale(): void
    {
        $redacteur = User::factory()->create(['role' => UserRole::Redacteur]);
        $this->actingAs($redacteur);

        $histoire = Histoire::factory()->pour($redacteur)->create();
        $scene = Scene::factory()->create(['dialogue_text' => 'Scène de départ']);
        $histoire->scenes()->attach($scene->id, ['est_initiale' => true]);
        $choix = Choix::factory()->favorable()->create(['scene_id' => $scene->id]);

        Livewire::test(PreviewHistoire::class, ['record' => $histoire->getKey()])
            ->call('choisir', $choix->id)
            ->call('recommencer')
            ->assertSee('Scène de départ');
    }

    public function test_la_preview_refuse_un_choix_d_une_autre_scene(): void
    {
        $redacteur = User::factory()->create(['role' => UserRole::Redacteur]);
        $this->actingAs($redacteur);

        $histoire = Histoire::factory()->pour($redacteur)->create();
        $scene = Scene::factory()->create();
        $histoire->scenes()->attach($scene->id, ['est_initiale' => true]);
        $etranger = Choix::factory()->favorable()->create();

        $this->expectException(ModelNotFoundException::class);

        Livewire::test(PreviewHistoire::class, ['record' => $histoire->getKey()])
            ->call('choisir', $etranger->id);
    }
}
