<?php

namespace Tests\Feature\Database;

use App\Enums\TelephoneType;
use App\Models\Contact;
use App\Models\SousTheme;
use App\Models\Telephone;
use Illuminate\Database\QueryException;
use Tests\TestCase;

class ContactTest extends TestCase
{
    public function test_un_contact_peut_exister_sans_sous_theme(): void
    {
        // Etat normal entre la creation et l'assignation. Invisible du front.
        $contact = Contact::factory()->create();

        $this->assertCount(0, $contact->sousThemes);
        $this->assertDatabaseHas('contacts', ['id' => $contact->id]);
    }

    public function test_un_contact_sert_plusieurs_sous_themes_et_inversement(): void
    {
        $contact = Contact::factory()->create();
        $sousThemes = SousTheme::factory()->count(3)->create();
        $contact->sousThemes()->attach($sousThemes->pluck('id'), ['ordre' => 0]);

        $autre = Contact::factory()->create();
        $autre->sousThemes()->attach($sousThemes->first()->id, ['ordre' => 0]);

        $this->assertCount(3, $contact->sousThemes);
        $this->assertCount(2, $sousThemes->first()->contacts);
    }

    public function test_l_ordre_du_pivot_pilote_l_affichage(): void
    {
        $sousTheme = SousTheme::factory()->create();
        $universitaire = Contact::factory()->create(['nom' => 'SUMPPS']);
        $associatif = Contact::factory()->create(['nom' => 'Association']);

        // Les contacts universitaires passent en tete.
        $sousTheme->contacts()->attach($associatif->id, ['ordre' => 10]);
        $sousTheme->contacts()->attach($universitaire->id, ['ordre' => 1]);

        $this->assertSame(
            ['SUMPPS', 'Association'],
            $sousTheme->contacts()->pluck('nom')->all()
        );
    }

    public function test_un_contact_possede_plusieurs_telephones_libelles(): void
    {
        $contact = Contact::factory()->create();
        Telephone::factory()->libelle('Psychologue')->create(['contact_id' => $contact->id]);
        Telephone::factory()->libelle('Intervenant social')->create(['contact_id' => $contact->id]);

        $this->assertCount(2, $contact->telephones);
        $this->assertContains('Psychologue', $contact->telephones->pluck('libelle')->all());
    }

    public function test_le_type_de_telephone_est_caste_en_enum(): void
    {
        $telephone = Telephone::factory()->urgence()->create();

        $this->assertInstanceOf(TelephoneType::class, $telephone->fresh()->type);
        $this->assertSame(TelephoneType::Urgence, $telephone->fresh()->type);
    }

    public function test_le_numero_vert_est_faux_par_defaut(): void
    {
        $contact = Contact::factory()->create();
        $telephone = Telephone::factory()->create(['contact_id' => $contact->id]);

        $this->assertFalse($telephone->fresh()->numero_vert);
    }

    public function test_le_numero_vert_peut_etre_marque_gratuit_depuis_un_fixe(): void
    {
        $contact = Contact::factory()->create();
        $telephone = Telephone::factory()->numeroVert()->create(['contact_id' => $contact->id]);

        $this->assertTrue($telephone->fresh()->numero_vert);
    }

    public function test_le_type_de_telephone_rejette_une_valeur_hors_liste(): void
    {
        $contact = Contact::factory()->create();

        $this->expectException(QueryException::class);

        \DB::table('telephones')->insert([
            'numero' => '00',
            'type' => 'fax',
            'contact_id' => $contact->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_supprimer_un_contact_efface_ses_donnees_liees_rgpd(): void
    {
        // Droit a l'effacement : nom, prenom et mail d'un referent nomme.
        // Assertions ciblees sur les enregistrements du test : la base de dev
        // contient deja des contacts et une taxonomie de reference.
        $contact = Contact::factory()->referentNomme()->create();
        $sousTheme = SousTheme::factory()->create();
        $contact->sousThemes()->attach($sousTheme->id, ['ordre' => 0]);
        Telephone::factory()->count(2)->create(['contact_id' => $contact->id]);

        $contact->delete();

        $this->assertModelMissing($contact);
        $this->assertDatabaseMissing('telephones', ['contact_id' => $contact->id]);
        $this->assertDatabaseMissing('contact_sous_theme', ['contact_id' => $contact->id]);
        // Le sous-theme, lui, survit.
        $this->assertModelExists($sousTheme);
    }

    public function test_le_scope_actif_ecarte_les_structures_fermees(): void
    {
        $ids = [
            ...Contact::factory()->count(2)->create()->modelKeys(),
            Contact::factory()->inactif()->create()->id,
        ];

        $this->assertSame(3, Contact::whereKey($ids)->count());
        $this->assertSame(2, Contact::whereKey($ids)->actif()->count());
    }

    public function test_les_criteres_filtrables_distinguent_inconnu_de_faux(): void
    {
        $inconnu = Contact::factory()->criteresInconnus()->create();
        $payant = Contact::factory()->create(['gratuit' => false]);

        $this->assertNull($inconnu->fresh()->gratuit);
        $this->assertFalse($payant->fresh()->gratuit);
    }
}
