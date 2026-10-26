<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Histoire;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Tests\TestCase;

class PurgeComptesInactifsTest extends TestCase
{
    private function compte(?UserRole $role, string $derniereConnexion = '-4 years', string $inscription = '-5 years'): User
    {
        return User::factory()->create([
            'role' => $role,
            'created_at' => now()->modify($inscription),
            'derniere_connexion_at' => now()->modify($derniereConnexion),
        ]);
    }

    public function test_un_compte_inactif_depuis_plus_de_3_ans_est_supprime(): void
    {
        $compte = $this->compte(UserRole::Webmaster);

        $this->artisan('comptes:purger-inactifs')->assertSuccessful();

        $this->assertModelMissing($compte);
    }

    public function test_un_compte_actif_depuis_moins_de_3_ans_est_conserve(): void
    {
        $compte = $this->compte(UserRole::Webmaster, '-2 years');

        $this->artisan('comptes:purger-inactifs')->assertSuccessful();

        $this->assertModelExists($compte);
    }

    public function test_un_compte_jamais_connecte_est_juge_sur_son_inscription(): void
    {
        $ancien = User::factory()->create(['role' => null, 'created_at' => now()->subYears(4)]);
        $recent = User::factory()->create(['role' => null, 'created_at' => now()->subYear()]);

        $this->artisan('comptes:purger-inactifs')->assertSuccessful();

        $this->assertModelMissing($ancien);
        $this->assertModelExists($recent);
    }

    public function test_les_admins_et_le_visiteur_ne_sont_jamais_purges(): void
    {
        $admin = $this->compte(UserRole::Admin);
        $visiteur = $this->compte(UserRole::Visiteur);

        $this->artisan('comptes:purger-inactifs')->assertSuccessful();

        $this->assertModelExists($admin);
        $this->assertModelExists($visiteur);
    }

    public function test_un_compte_avec_des_histoires_est_ignore(): void
    {
        $redacteur = $this->compte(UserRole::Redacteur);
        $histoire = Histoire::factory()->create(['user_id' => $redacteur->id]);

        $this->artisan('comptes:purger-inactifs')->assertSuccessful();

        $this->assertModelExists($redacteur);
        $this->assertModelExists($histoire);
    }

    public function test_la_simulation_ne_supprime_rien(): void
    {
        $compte = $this->compte(UserRole::Webmaster);

        $this->artisan('comptes:purger-inactifs --simulation')
            ->expectsOutputToContain($compte->email)
            ->assertSuccessful();

        $this->assertModelExists($compte);
    }

    public function test_la_connexion_met_a_jour_la_derniere_connexion(): void
    {
        $compte = $this->compte(UserRole::Redacteur);

        event(new Login('web', $compte, false));

        $this->assertTrue($compte->fresh()->derniere_connexion_at->isToday());
    }

    public function test_la_tache_est_planifiee_chaque_jour(): void
    {
        $this->artisan('schedule:list')
            ->expectsOutputToContain('comptes:purger-inactifs')
            ->assertSuccessful();
    }
}
