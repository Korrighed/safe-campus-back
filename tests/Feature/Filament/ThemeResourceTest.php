<?php

namespace Tests\Feature\Filament;

use App\Enums\MediaType;
use App\Enums\UserRole;
use App\Filament\Resources\ThemeResource\Pages\CreateTheme;
use App\Filament\Resources\ThemeResource\Pages\EditTheme;
use App\Filament\Resources\ThemeResource\Pages\ListThemes;
use App\Filament\Resources\ThemeResource\RelationManagers\MediasRelationManager;
use App\Filament\Resources\ThemeResource\RelationManagers\SousThemesRelationManager;
use App\Models\Media;
use App\Models\Theme;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class ThemeResourceTest extends TestCase
{
    public function test_un_redacteur_est_refuse_sur_la_liste(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Redacteur]));

        Livewire::test(ListThemes::class)->assertForbidden();
    }

    public function test_un_compte_sans_role_est_refuse_sur_la_liste(): void
    {
        $this->actingAs(User::factory()->create(['role' => null]));

        Livewire::test(ListThemes::class)->assertForbidden();
    }

    public function test_un_webmaster_accede_a_la_liste(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Webmaster]));

        Livewire::test(ListThemes::class)->assertSuccessful();
    }

    public function test_un_webmaster_cree_un_theme(): void
    {
        // Refs distinctes de la taxonomie posee par la migration
        // 2026_07_31_120000_seed_taxonomie_themes_sous_themes (deja en base
        // via DatabaseTransactions) : eviter toute collision sur `ref`.
        $this->actingAs(User::factory()->create(['role' => UserRole::Webmaster]));

        Livewire::test(CreateTheme::class)
            ->fillForm([
                'ref' => 'theme_test',
                'libelle' => 'Thème de test',
                'resume' => 'Teaser de test.',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('themes', ['ref' => 'theme_test', 'libelle' => 'Thème de test']);
    }

    public function test_le_ref_est_verrouille_en_edition(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Webmaster]));
        $theme = Theme::factory()->create(['ref' => 'theme_verrouille', 'libelle' => 'Avant modification']);

        Livewire::test(EditTheme::class, ['record' => $theme->getKey()])
            ->fillForm(['ref' => 'autre_ref', 'libelle' => 'Après modification'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('themes', ['id' => $theme->id, 'ref' => 'theme_verrouille', 'libelle' => 'Après modification']);
    }

    public function test_un_webmaster_edite_le_libelle_court_et_l_ordre(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Webmaster]));
        $theme = Theme::factory()->create(['libelle_court' => null, 'ordre' => 0]);

        Livewire::test(EditTheme::class, ['record' => $theme->getKey()])
            ->fillForm(['libelle_court' => 'Addictions', 'ordre' => 2])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('themes', ['id' => $theme->id, 'libelle_court' => 'Addictions', 'ordre' => 2]);
    }

    public function test_le_relation_manager_cree_un_sous_theme_rattache_au_theme(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Webmaster]));
        $theme = Theme::factory()->create(['ref' => 'theme_parent_test']);

        Livewire::test(SousThemesRelationManager::class, [
            'ownerRecord' => $theme,
            'pageClass' => EditTheme::class,
        ])
            ->callTableAction('create', data: [
                'ref' => 'sous_theme_test',
                'libelle' => 'Sous-thème de test',
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('sous_themes', ['ref' => 'sous_theme_test', 'theme_id' => $theme->id]);
    }

    public function test_le_relation_manager_medias_attache_un_media_existant(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Webmaster]));
        $theme = Theme::factory()->create();
        $media = Media::factory()->type(MediaType::Video)->create();

        Livewire::test(MediasRelationManager::class, [
            'ownerRecord' => $theme,
            'pageClass' => EditTheme::class,
        ])
            ->callTableAction('attach', data: [
                'recordId' => $media->id,
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('media_theme', [
            'theme_id' => $theme->id,
            'media_id' => $media->id,
        ]);
    }
}
