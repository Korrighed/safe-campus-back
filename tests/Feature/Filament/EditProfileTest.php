<?php

namespace Tests\Feature\Filament;

use App\Enums\UserRole;
use App\Filament\Pages\Auth\EditProfile;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class EditProfileTest extends TestCase
{
    private const MOT_DE_PASSE = 'Campus-Securise-2026!';

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake(['api.pwnedpasswords.com/*' => fn () => Http::response('')]);
    }

    public function test_le_formulaire_est_pre_rempli_avec_les_donnees_du_compte(): void
    {
        $user = User::factory()->create([
            'prenom' => 'Jeanne',
            'nom' => 'Wamytan',
            'telephone' => '+687771234',
            'role' => UserRole::Redacteur,
        ]);

        $this->actingAs($user);

        Livewire::test(EditProfile::class)
            ->assertFormSet([
                'prenom' => 'Jeanne',
                'nom' => 'Wamytan',
                'email' => $user->email,
                'telephone' => '+687771234',
            ]);
    }

    public function test_le_role_est_affiche_en_lecture_seule(): void
    {
        $user = User::factory()->create(['role' => UserRole::Webmaster]);
        $this->actingAs($user);

        Livewire::test(EditProfile::class)
            ->assertSee('Webmaster');
    }

    public function test_modifie_identite_et_telephone(): void
    {
        $user = User::factory()->create(['role' => UserRole::Redacteur]);
        $this->actingAs($user);

        Livewire::test(EditProfile::class)
            ->fillForm([
                'prenom' => 'Nouveau',
                'nom' => 'Nom',
                'email' => $user->email,
                'telephone' => '77 12 34',
                'password' => null,
                'passwordConfirmation' => null,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();
        $this->assertSame('Nouveau', $user->prenom);
        $this->assertSame('Nom', $user->nom);
        $this->assertSame('+687771234', $user->telephone);
    }

    public function test_email_deja_utilise_par_un_autre_compte_est_refuse(): void
    {
        User::factory()->create(['email' => 'existant@test.nc']);
        $user = User::factory()->create(['role' => UserRole::Redacteur]);
        $this->actingAs($user);

        Livewire::test(EditProfile::class)
            ->fillForm([
                'email' => 'EXISTANT@test.nc',
                'telephone' => $user->telephone,
                'password' => null,
                'passwordConfirmation' => null,
            ])
            ->call('save')
            ->assertHasFormErrors(['email']);
    }

    public function test_garder_sa_propre_adresse_email_est_accepte(): void
    {
        $user = User::factory()->create(['role' => UserRole::Redacteur]);
        $this->actingAs($user);

        Livewire::test(EditProfile::class)
            ->fillForm([
                'email' => mb_strtoupper($user->email),
                'telephone' => $user->telephone,
                'password' => null,
                'passwordConfirmation' => null,
            ])
            ->call('save')
            ->assertHasNoFormErrors();
    }

    public function test_mot_de_passe_laisse_vide_ne_change_rien(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Redacteur,
            'password' => 'ancien-mot-de-passe',
        ]);
        $hashAvant = $user->password;
        $this->actingAs($user);

        Livewire::test(EditProfile::class)
            ->fillForm([
                'email' => $user->email,
                'telephone' => $user->telephone,
                'password' => null,
                'passwordConfirmation' => null,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();
        $this->assertSame($hashAvant, $user->password);
    }

    public function test_mot_de_passe_renseigne_est_change(): void
    {
        $user = User::factory()->create(['role' => UserRole::Redacteur]);
        $this->actingAs($user);

        Livewire::test(EditProfile::class)
            ->fillForm([
                'email' => $user->email,
                'telephone' => $user->telephone,
                'password' => self::MOT_DE_PASSE,
                'passwordConfirmation' => self::MOT_DE_PASSE,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();
        $this->assertTrue(Hash::check(self::MOT_DE_PASSE, $user->password));
    }

    public function test_telephone_invalide_est_refuse(): void
    {
        $user = User::factory()->create(['role' => UserRole::Redacteur]);
        $this->actingAs($user);

        Livewire::test(EditProfile::class)
            ->fillForm([
                'email' => $user->email,
                'telephone' => '123',
                'password' => null,
                'passwordConfirmation' => null,
            ])
            ->call('save')
            ->assertHasFormErrors(['telephone']);
    }

    /**
     * Regression : ->tel() de Filament ajoute sa propre regex generique, non
     * traduite en francais, en plus de la regle metier — le message affiche
     * etait la cle brute "validation.regex" au lieu du message specifique.
     */
    public function test_telephone_invalide_affiche_un_message_en_francais(): void
    {
        $user = User::factory()->create(['role' => UserRole::Redacteur]);
        $this->actingAs($user);

        Livewire::test(EditProfile::class)
            ->fillForm([
                'email' => $user->email,
                'telephone' => '98425DSFDS',
                'password' => null,
                'passwordConfirmation' => null,
            ])
            ->call('save')
            ->assertHasFormErrors([
                'telephone' => 'Le numéro doit être un numéro calédonien à 6 chiffres.',
            ]);
    }
}
