<?php

namespace Tests\Feature\Database;

use App\Enums\MediaType;
use App\Models\Media;
use App\Models\SousTheme;
use App\Models\Theme;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MediaTest extends TestCase
{
    public function test_la_table_est_medias_et_non_media(): void
    {
        // L'inflecteur Laravel rend « media » : sans $table explicite, le modele
        // interrogerait une table qui n'existe pas.
        $this->assertSame('medias', (new Media)->getTable());
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('medias'));
        $this->assertFalse(DB::getSchemaBuilder()->hasTable('media'));
    }

    public function test_le_libelle_permet_de_retrouver_un_media(): void
    {
        Media::factory()->create(['libelle' => 'Visuel campagne alcool']);
        Media::factory()->count(5)->create();

        $trouve = Media::where('libelle', 'like', '%campagne%')->first();

        $this->assertNotNull($trouve);
        $this->assertSame('Visuel campagne alcool', $trouve->libelle);
    }

    public function test_le_type_est_caste_en_enum(): void
    {
        $media = Media::factory()->type(MediaType::Video)->create();

        $this->assertInstanceOf(MediaType::class, $media->fresh()->type);
        $this->assertSame(MediaType::Video, $media->fresh()->type);
    }

    public function test_un_media_est_mutualisable_entre_plusieurs_sous_themes(): void
    {
        $media = Media::factory()->create();
        $sousThemes = SousTheme::factory()->count(2)->create();
        $media->sousThemes()->attach($sousThemes->pluck('id'));

        $this->assertCount(2, $media->sousThemes);
        $this->assertCount(1, $sousThemes->first()->medias);
    }

    public function test_supprimer_un_sous_theme_detache_ses_medias_sans_les_supprimer(): void
    {
        $media = Media::factory()->create();
        $sousTheme = SousTheme::factory()->create();
        $sousTheme->medias()->attach($media->id);

        $sousTheme->delete();

        $this->assertDatabaseMissing('media_sous_theme', ['media_id' => $media->id]);
        $this->assertModelExists($media);
    }

    public function test_un_theme_peut_porter_plusieurs_medias_non_exclusifs(): void
    {
        // Texte (Theme.resume), logo et video coexistent, aucun n'exclut les autres.
        $theme = Theme::factory()->create(['resume' => 'Petit texte de presentation.']);
        $logo = Media::factory()->type(MediaType::Image)->create();
        $video = Media::factory()->type(MediaType::Video)->create();
        $theme->medias()->attach([$logo->id, $video->id]);

        $this->assertNotNull($theme->resume);
        $this->assertCount(2, $theme->medias);
        $this->assertCount(1, $logo->themes);
    }

    public function test_supprimer_un_theme_detache_ses_medias_sans_les_supprimer(): void
    {
        $media = Media::factory()->create();
        $theme = Theme::factory()->create();
        $theme->medias()->attach($media->id);

        $theme->delete();

        $this->assertDatabaseMissing('media_theme', ['media_id' => $media->id]);
        $this->assertModelExists($media);
    }
}
