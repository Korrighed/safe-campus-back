<?php

namespace Tests\Feature\Filament;

use App\Enums\UserRole;
use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Auth\Register;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class InscriptionTest extends TestCase
{
    private const MOT_DE_PASSE = 'Campus-Securise-2026!';

    /**
     * Corps renvoye par le faux api.pwnedpasswords.com : vide = mot de passe
     * absent des fuites. Aucun appel reseau reel pendant les tests.
     */
    private string $reponsePwned = '';

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake(['api.pwnedpasswords.com/*' => fn () => Http::response($this->reponsePwned)]);
    }

    /**
     * @param  array<string, string>  $surcharges
     * @return array<string, string>
     */
    private function formulaire(array $surcharges = []): array
    {
        return array_merge([
            'prenom' => 'Jeanne',
            'nom' => 'Wamytan',
            'email' => 'jeanne.'.uniqid().'@test.nc',
            'telephone' => '77 12 34',
            'password' => self::MOT_DE_PASSE,
            'passwordConfirmation' => self::MOT_DE_PASSE,
        ], $surcharges);
    }

    public function test_la_page_de_connexion_propose_l_inscription(): void
    {
        $this->get(Filament::getLoginUrl())
            ->assertSuccessful()
            ->assertSee(Filament::getRegistrationUrl());
    }

    public function test_l_inscription_cree_un_compte_sans_role_non_connecte(): void
    {
        $donnees = $this->formulaire();

        Livewire::test(Register::class)
            ->fillForm($donnees)
            ->call('register')
            ->assertHasNoFormErrors()
            ->assertRedirect(Filament::getLoginUrl());

        $user = User::where('email', $donnees['email'])->firstOrFail();

        $this->assertSame('Jeanne', $user->prenom);
        $this->assertSame('Wamytan', $user->nom);
        $this->assertSame('+687771234', $user->telephone);
        $this->assertNull($user->role);
        $this->assertGuest();
    }

    public function test_l_email_est_stocke_en_minuscules(): void
    {
        $email = 'Jeanne.'.uniqid().'@Test.NC';

        Livewire::test(Register::class)
            ->fillForm($this->formulaire(['email' => $email]))
            ->call('register')
            ->assertHasNoFormErrors();

        $this->assertTrue(User::where('email', strtolower($email))->exists());
    }

    public function test_un_email_deja_inscrit_avec_une_autre_casse_est_refuse(): void
    {
        $user = User::factory()->create(['email' => 'casse.'.uniqid().'@test.nc']);

        Livewire::test(Register::class)
            ->fillForm($this->formulaire(['email' => strtoupper($user->email)]))
            ->call('register')
            ->assertHasFormErrors(['email']);
    }

    public function test_la_connexion_ignore_la_casse_de_l_email(): void
    {
        $user = User::factory()->create(['role' => UserRole::Redacteur, 'password' => self::MOT_DE_PASSE]);

        Livewire::test(Login::class)
            ->fillForm(['email' => strtoupper($user->email), 'password' => self::MOT_DE_PASSE])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($user);
    }

    public function test_un_telephone_envoye_en_tableau_est_refuse_sans_erreur(): void
    {
        // Requete Livewire forgee : un tableau a la place d'une chaine.
        Livewire::test(Register::class)
            ->fillForm($this->formulaire())
            ->set('data.telephone', ['77', '12', '34'])
            ->call('register')
            ->assertHasFormErrors(['telephone']);
    }

    public function test_le_telephone_accepte_le_prefixe_687(): void
    {
        $donnees = $this->formulaire(['telephone' => '+687 25.12.34']);

        Livewire::test(Register::class)
            ->fillForm($donnees)
            ->call('register')
            ->assertHasNoFormErrors();

        $this->assertSame('+687251234', User::where('email', $donnees['email'])->value('telephone'));
    }

    /**
     * Un cas par test : register() est limite a 2 appels par minute.
     *
     * @return array<string, array{string}>
     */
    public static function telephonesInvalides(): array
    {
        return [
            'commence par 1' => ['12 34 56'],
            '7 chiffres' => ['771234567'],
            'metropole' => ['+33 6 12 34 56 78'],
            'lettres' => ['abcdef'],
        ];
    }

    #[DataProvider('telephonesInvalides')]
    public function test_le_telephone_hors_format_caledonien_est_refuse(string $numero): void
    {
        Livewire::test(Register::class)
            ->fillForm($this->formulaire(['telephone' => $numero]))
            ->call('register')
            ->assertHasFormErrors(['telephone']);
    }

    public function test_les_champs_sont_obligatoires(): void
    {
        Livewire::test(Register::class)
            ->fillForm($this->formulaire([
                'prenom' => '',
                'nom' => '',
                'telephone' => '',
            ]))
            ->call('register')
            ->assertHasFormErrors(['prenom' => 'required', 'nom' => 'required', 'telephone' => 'required']);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function motsDePasseInvalides(): array
    {
        return [
            'moins de 14 caracteres' => ['Court-2026!'],
            'sans majuscule' => ['campus-securise-2026!'],
            'sans caractere special' => ['CampusSecurise2026'],
        ];
    }

    #[DataProvider('motsDePasseInvalides')]
    public function test_le_mot_de_passe_respecte_la_politique(string $motDePasse): void
    {
        Livewire::test(Register::class)
            ->fillForm($this->formulaire([
                'password' => $motDePasse,
                'passwordConfirmation' => $motDePasse,
            ]))
            ->call('register')
            ->assertHasFormErrors(['password']);
    }

    public function test_un_mot_de_passe_ayant_fuite_est_refuse(): void
    {
        $this->reponsePwned = substr(strtoupper(sha1(self::MOT_DE_PASSE)), 5).':42';

        Livewire::test(Register::class)
            ->fillForm($this->formulaire())
            ->call('register')
            ->assertHasFormErrors(['password']);
    }

    public function test_un_compte_en_attente_recoit_un_message_explicite(): void
    {
        $user = User::factory()->create(['role' => null, 'password' => self::MOT_DE_PASSE]);

        Livewire::test(Login::class)
            ->fillForm(['email' => $user->email, 'password' => self::MOT_DE_PASSE])
            ->call('authenticate')
            ->assertHasFormErrors(['email']);

        $this->assertGuest();
    }

    public function test_un_compte_visiteur_est_refuse_sans_message_d_attente(): void
    {
        // Le role Visiteur sert l'API du front, jamais /admin : echec
        // generique, pas le message reserve aux inscriptions en attente.
        $user = User::factory()->create(['role' => UserRole::Visiteur, 'password' => self::MOT_DE_PASSE]);

        Livewire::test(Login::class)
            ->fillForm(['email' => $user->email, 'password' => self::MOT_DE_PASSE])
            ->call('authenticate')
            ->assertHasFormErrors(['email'])
            ->assertDontSee('en attente de validation');

        $this->assertGuest();
    }

    public function test_un_compte_avec_role_se_connecte(): void
    {
        $user = User::factory()->create(['role' => UserRole::Redacteur, 'password' => self::MOT_DE_PASSE]);

        Livewire::test(Login::class)
            ->fillForm(['email' => $user->email, 'password' => self::MOT_DE_PASSE])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($user);
    }
}
