<?php

namespace Tests\Feature\Filament;

use App\Enums\UserRole;
use App\Filament\Resources\ContactResource\Pages\ListContacts;
use App\Filament\Resources\HistoireResource\Pages\ListHistoires;
use App\Filament\Resources\MediaResource\Pages\ListMedia;
use App\Filament\Resources\ThemeResource\Pages\ListThemes;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\Histoire;
use App\Models\User;
use Filament\Panel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UserResourceTest extends TestCase
{
    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin]);
    }

    public function test_le_premier_admin_est_provisionne_au_migrate(): void
    {
        $this->assertTrue(
            User::where('email', config('admin.email'))->where('role', UserRole::Admin->value)->exists()
        );
    }

    public function test_un_admin_accede_au_panel(): void
    {
        $this->assertTrue($this->admin()->canAccessPanel(Panel::make()));
    }

    /**
     * @return array<string, array{?UserRole}>
     */
    public static function rolesNonAdmin(): array
    {
        return [
            'webmaster' => [UserRole::Webmaster],
            'redacteur' => [UserRole::Redacteur],
            'sans role' => [null],
        ];
    }

    #[DataProvider('rolesNonAdmin')]
    public function test_seul_l_admin_accede_a_la_liste(?UserRole $role): void
    {
        $this->actingAs(User::factory()->create(['role' => $role]));

        Livewire::test(ListUsers::class)->assertForbidden();
    }

    /**
     * @return array<string, array{class-string}>
     */
    public static function ressourcesMetier(): array
    {
        return [
            'themes' => [ListThemes::class],
            'contacts' => [ListContacts::class],
            'medias' => [ListMedia::class],
            'histoires' => [ListHistoires::class],
        ];
    }

    #[DataProvider('ressourcesMetier')]
    public function test_l_admin_ne_voit_pas_les_ressources_metier(string $page): void
    {
        $this->actingAs($this->admin());

        Livewire::test($page)->assertForbidden();
    }

    public function test_la_liste_montre_les_comptes_mais_jamais_le_visiteur(): void
    {
        $this->actingAs($this->admin());
        $enAttente = User::factory()->create(['role' => null]);
        $redacteur = User::factory()->create(['role' => UserRole::Redacteur]);
        $visiteur = User::factory()->create(['role' => UserRole::Visiteur]);

        Livewire::test(ListUsers::class)
            ->assertCanSeeTableRecords([$enAttente, $redacteur])
            ->assertCanNotSeeTableRecords([$visiteur]);
    }

    public function test_le_filtre_en_attente_ne_garde_que_les_comptes_sans_role(): void
    {
        $this->actingAs($this->admin());
        $enAttente = User::factory()->create(['role' => null]);
        $webmaster = User::factory()->create(['role' => UserRole::Webmaster]);

        Livewire::test(ListUsers::class)
            ->filterTable('en_attente')
            ->assertCanSeeTableRecords([$enAttente])
            ->assertCanNotSeeTableRecords([$webmaster]);
    }

    public function test_l_admin_attribue_un_role_a_une_demande(): void
    {
        $this->actingAs($this->admin());
        $user = User::factory()->create(['role' => null]);

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->fillForm(['role' => UserRole::Redacteur->value])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(UserRole::Redacteur, $user->fresh()->role);
    }

    public function test_l_admin_nomme_un_autre_admin(): void
    {
        $this->actingAs($this->admin());
        $user = User::factory()->create(['role' => UserRole::Webmaster]);

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->fillForm(['role' => UserRole::Admin->value])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(UserRole::Admin, $user->fresh()->role);
    }

    public function test_l_admin_retire_un_role_sans_supprimer_le_compte(): void
    {
        $this->actingAs($this->admin());
        $user = User::factory()->create(['role' => UserRole::Redacteur]);

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->fillForm(['role' => null])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNull($user->fresh()->role);
    }

    public function test_le_role_visiteur_ne_peut_pas_etre_attribue(): void
    {
        $this->actingAs($this->admin());
        $user = User::factory()->create(['role' => null]);

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->fillForm(['role' => UserRole::Visiteur->value])
            ->call('save')
            ->assertHasFormErrors(['role']);

        $this->assertNull($user->fresh()->role);
    }

    public function test_seul_le_role_est_modifiable(): void
    {
        $this->actingAs($this->admin());
        $user = User::factory()->create(['role' => null, 'email' => 'origine.'.uniqid().'@test.nc']);

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->set('data.email', 'pirate@test.nc')
            ->set('data.nom', 'Pirate')
            ->fillForm(['role' => UserRole::Webmaster->value])
            ->call('save');

        $this->assertNotSame('pirate@test.nc', $user->fresh()->email);
        $this->assertNotSame('Pirate', $user->fresh()->nom);
    }

    public function test_un_admin_ne_peut_pas_modifier_son_propre_compte(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        Livewire::test(EditUser::class, ['record' => $admin->getRouteKey()])->assertForbidden();
    }

    public function test_le_compte_visiteur_est_introuvable_depuis_la_ressource(): void
    {
        // Exclu de getEloquentQuery() : 404, pas meme un 403.
        $this->actingAs($this->admin());
        $visiteur = User::factory()->create(['role' => UserRole::Visiteur]);

        $this->expectException(ModelNotFoundException::class);

        Livewire::test(EditUser::class, ['record' => $visiteur->getRouteKey()]);
    }

    public function test_l_admin_refuse_une_demande(): void
    {
        $this->actingAs($this->admin());
        $user = User::factory()->create(['role' => null]);

        Livewire::test(ListUsers::class)->callTableAction('supprimer', $user);

        $this->assertModelMissing($user);
    }

    public function test_l_admin_supprime_un_compte_actif_sans_histoire(): void
    {
        $this->actingAs($this->admin());
        $user = User::factory()->create(['role' => UserRole::Webmaster]);

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->callAction('supprimer')
            ->assertRedirect(UserResource::getUrl('index'));

        $this->assertModelMissing($user);
    }

    public function test_les_histoires_d_un_redacteur_supprime_sont_transferees(): void
    {
        $this->actingAs($this->admin());
        $partant = User::factory()->create(['role' => UserRole::Redacteur]);
        $repreneur = User::factory()->create(['role' => UserRole::Redacteur]);
        $histoires = Histoire::factory()->count(2)->create(['user_id' => $partant->id]);

        Livewire::test(ListUsers::class)
            ->callTableAction('supprimer', $partant, data: ['repreneur_id' => $repreneur->id])
            ->assertHasNoTableActionErrors();

        $this->assertModelMissing($partant);
        foreach ($histoires as $histoire) {
            $this->assertSame($repreneur->id, $histoire->fresh()->user_id);
        }
    }

    public function test_sans_repreneur_le_redacteur_et_ses_histoires_sont_conserves(): void
    {
        $this->actingAs($this->admin());
        $partant = User::factory()->create(['role' => UserRole::Redacteur]);
        $histoire = Histoire::factory()->create(['user_id' => $partant->id]);

        Livewire::test(ListUsers::class)
            ->callTableAction('supprimer', $partant, data: ['repreneur_id' => null])
            ->assertHasTableActionErrors(['repreneur_id' => 'required']);

        $this->assertModelExists($partant);
        $this->assertModelExists($histoire);
    }

    public function test_le_repreneur_doit_etre_un_redacteur(): void
    {
        $this->actingAs($this->admin());
        $partant = User::factory()->create(['role' => UserRole::Redacteur]);
        $webmaster = User::factory()->create(['role' => UserRole::Webmaster]);
        $histoire = Histoire::factory()->create(['user_id' => $partant->id]);

        // Requete forgee : un webmaster n'est pas dans la liste proposee.
        Livewire::test(ListUsers::class)
            ->callTableAction('supprimer', $partant, data: ['repreneur_id' => $webmaster->id]);

        $this->assertModelExists($partant);
        $this->assertSame($partant->id, $histoire->fresh()->user_id);
    }

    public function test_un_admin_ne_peut_pas_se_supprimer(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        Livewire::test(ListUsers::class)->assertTableActionHidden('supprimer', $admin);

        $this->assertModelExists($admin);
    }
}
