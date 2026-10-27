<?php

namespace Tests\Feature\Filament;

use App\Enums\TelephoneType;
use App\Enums\UserRole;
use App\Filament\Resources\ContactResource\Pages\CreateContact;
use App\Filament\Resources\ContactResource\Pages\EditContact;
use App\Filament\Resources\ContactResource\Pages\ListContacts;
use App\Filament\Resources\ContactResource\RelationManagers\SousThemesRelationManager;
use App\Filament\Resources\ContactResource\RelationManagers\TelephonesRelationManager;
use App\Models\Contact;
use App\Models\SousTheme;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class ContactResourceTest extends TestCase
{
    public function test_un_redacteur_est_refuse_sur_la_liste(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Redacteur]));

        Livewire::test(ListContacts::class)->assertForbidden();
    }

    public function test_un_webmaster_accede_a_la_liste(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Webmaster]));

        Livewire::test(ListContacts::class)->assertSuccessful();
    }

    public function test_la_liste_affiche_les_numeros_de_telephone(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Webmaster]));
        $contact = Contact::factory()->create();
        $contact->telephones()->create([
            'numero' => '25 07 60',
            'type' => TelephoneType::Fixe,
        ]);

        // Recherche ciblee : la liste pagine aussi les contacts deja en base.
        Livewire::test(ListContacts::class)
            ->searchTable($contact->nom)
            ->assertSee('25 07 60');
    }

    public function test_un_webmaster_cree_un_contact_sans_sous_theme(): void
    {
        // Etat normal entre la creation et l'assignation — voir docs/schema_bd.md § Contact.
        // Le champ ref n'existe plus dans le formulaire : le webmaster n'a pas a le renseigner.
        $this->actingAs(User::factory()->create(['role' => UserRole::Webmaster]));

        Livewire::test(CreateContact::class)
            ->fillForm([
                'nom' => 'Structure de test',
                'actif' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('contacts', ['ref' => 'structure_de_test', 'nom' => 'Structure de test']);
    }

    public function test_le_ref_genere_se_deduplique_en_cas_de_collision(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Webmaster]));
        Contact::factory()->create(['ref' => 'structure_doublon', 'nom' => 'Structure doublon']);

        Livewire::test(CreateContact::class)
            ->fillForm([
                'nom' => 'Structure doublon',
                'actif' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('contacts', ['ref' => 'structure_doublon_2', 'nom' => 'Structure doublon']);
    }

    public function test_le_ref_reste_inchange_a_l_edition(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Webmaster]));
        $contact = Contact::factory()->create(['ref' => 'ref_edition_test', 'nom' => 'Structure edition']);

        Livewire::test(EditContact::class, ['record' => $contact->getKey()])
            ->fillForm(['nom' => 'Structure edition (modifiee)'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('contacts', ['id' => $contact->id, 'ref' => 'ref_edition_test', 'nom' => 'Structure edition (modifiee)']);
    }

    public function test_le_relation_manager_telephones_cree_un_numero(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Webmaster]));
        $contact = Contact::factory()->create();

        Livewire::test(TelephonesRelationManager::class, [
            'ownerRecord' => $contact,
            'pageClass' => EditContact::class,
        ])
            ->callTableAction('create', data: [
                'numero' => '15',
                'type' => TelephoneType::Urgence->value,
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('telephones', ['contact_id' => $contact->id, 'numero' => '15']);
    }

    public function test_le_relation_manager_sous_themes_attache_avec_un_ordre(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Webmaster]));
        $contact = Contact::factory()->create();
        $sousTheme = SousTheme::factory()->create();

        Livewire::test(SousThemesRelationManager::class, [
            'ownerRecord' => $contact,
            'pageClass' => EditContact::class,
        ])
            ->callTableAction('attach', data: [
                'recordId' => $sousTheme->getKey(),
                'ordre' => 2,
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('contact_sous_theme', [
            'contact_id' => $contact->id,
            'sous_theme_id' => $sousTheme->id,
            'ordre' => 2,
        ]);
    }

    public function test_un_webmaster_geocode_un_contact(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Webmaster]));
        $contact = Contact::factory()->create(['latitude' => null, 'longitude' => null]);

        Livewire::test(EditContact::class, ['record' => $contact->getKey()])
            ->fillForm(['latitude' => -22.2758, 'longitude' => 166.4580])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('contacts', ['id' => $contact->id, 'latitude' => -22.2758, 'longitude' => 166.4580]);
    }

    public function test_effacer_position_vide_le_formulaire_sans_persister_avant_enregistrement(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Webmaster]));
        $contact = Contact::factory()->create(['latitude' => -22.0, 'longitude' => 166.0]);

        Livewire::test(EditContact::class, ['record' => $contact->getKey()])
            ->mountFormComponentAction('localisation', 'effacer_position')
            ->callMountedFormComponentAction()
            ->assertHasNoFormComponentActionErrors()
            ->assertFormSet(['latitude' => null, 'longitude' => null]);

        // Tant que "Enregistrer" n'a pas ete clique, la position en base est inchangee.
        $this->assertDatabaseHas('contacts', ['id' => $contact->id, 'latitude' => -22.0, 'longitude' => 166.0]);
    }

    public function test_position_territoire_efface_les_coordonnees_a_l_enregistrement(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Webmaster]));
        $contact = Contact::factory()->create(['latitude' => -22.0, 'longitude' => 166.0]);

        Livewire::test(EditContact::class, ['record' => $contact->getKey()])
            ->set('data.position_territoire', true)
            ->assertFormSet(['latitude' => null, 'longitude' => null])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'latitude' => null,
            'longitude' => null,
            'position_territoire' => true,
        ]);
    }

    public function test_un_contact_deja_territoire_reste_editable_sans_toucher_au_toggle(): void
    {
        // Cas des contacts seedes (SAMU, Police...) : la grille lat/long est
        // masquee des le premier rendu, sans jamais passer par afterStateUpdated.
        $this->actingAs(User::factory()->create(['role' => UserRole::Webmaster]));
        $contact = Contact::factory()->create([
            'nom' => 'Avant',
            'position_territoire' => true,
            'latitude' => null,
            'longitude' => null,
        ]);

        Livewire::test(EditContact::class, ['record' => $contact->getKey()])
            ->fillForm(['nom' => 'Apres'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'nom' => 'Apres',
            'latitude' => null,
            'longitude' => null,
            'position_territoire' => true,
        ]);
    }

    public function test_les_assets_leaflet_sont_charges_meme_si_la_carte_est_masquee(): void
    {
        // La carte peut n'apparaitre qu'apres coup (toggle "tout le territoire"
        // desactive) : Leaflet doit deja etre charge sur la page, pas seulement
        // injecte par le champ carte quand il se rend.
        $this->actingAs(User::factory()->create(['role' => UserRole::Webmaster]));
        $contact = Contact::factory()->create(['position_territoire' => true, 'latitude' => null, 'longitude' => null]);

        $this->get(route('filament.admin.resources.contacts.edit', $contact))
            ->assertOk()
            ->assertSee('unpkg.com/leaflet', escape: false);
    }

    public function test_desactiver_position_territoire_permet_de_geocoder_a_nouveau(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                ['lat' => '-22.1234', 'lon' => '166.4321'],
            ], 200),
        ]);

        $this->actingAs(User::factory()->create(['role' => UserRole::Webmaster]));
        $contact = Contact::factory()->create([
            'position_territoire' => true,
            'latitude' => null,
            'longitude' => null,
            'localisation' => 'Adresse test',
        ]);

        Livewire::test(EditContact::class, ['record' => $contact->getKey()])
            ->set('data.position_territoire', false)
            ->mountFormComponentAction('localisation', 'geocoder')
            ->callMountedFormComponentAction()
            ->assertHasNoFormComponentActionErrors()
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'latitude' => -22.1234,
            'longitude' => 166.4321,
            'position_territoire' => false,
        ]);
    }
}
