<?php

namespace Tests\Feature\Database;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Panel;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserRoleTest extends TestCase
{
    public function test_le_role_est_caste_en_enum(): void
    {
        $user = User::factory()->create(['role' => UserRole::Webmaster]);

        $this->assertInstanceOf(UserRole::class, $user->fresh()->role);
        $this->assertSame(UserRole::Webmaster, $user->fresh()->role);
    }

    public function test_un_compte_avec_role_accede_au_panel(): void
    {
        $panel = Panel::make();

        $this->assertTrue(User::factory()->create(['role' => UserRole::Webmaster])->canAccessPanel($panel));
        $this->assertTrue(User::factory()->create(['role' => UserRole::Redacteur])->canAccessPanel($panel));
    }

    public function test_un_compte_sans_role_est_refuse_sur_le_panel(): void
    {
        // Inscription en attente de validation : refuse a la connexion.
        $user = User::factory()->create(['role' => null]);

        $this->assertFalse($user->canAccessPanel(Panel::make()));
    }

    public function test_le_role_rejette_une_valeur_hors_liste(): void
    {
        $this->expectException(QueryException::class);

        \DB::table('users')->insert([
            'nom' => 'x',
            'email' => 'x@test.nc',
            'password' => 'x',
            'role' => 'administrateur',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_users_et_contacts_ne_partagent_aucune_cle(): void
    {
        // Separation stricte : un intervenant de l'annuaire n'est pas un compte.
        $this->assertNotContains('contact_id', Schema::getColumnListing('users'));
        $this->assertNotContains('user_id', Schema::getColumnListing('contacts'));
    }
}
