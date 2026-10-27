<?php

namespace Tests\Feature\Database;

use App\Models\Histoire;
use Database\Seeders\HistoireAnxieteSeeder;
use Database\Seeders\HistoireDemoSeeder;
use Database\Seeders\HistoireHarcelementSeeder;
use Database\Seeders\HistoireSeeder;
use Database\Seeders\RedacteurDemoSeeder;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class HistoireDemoSeederTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->seed(RedacteurDemoSeeder::class);
    }

    /**
     * @return array<string, array{class-string<HistoireDemoSeeder>, string, string, int, int, string, string}>
     */
    public static function histoires(): array
    {
        return [
            'alcool' => [HistoireSeeder::class, 'demo_soiree_qui_derape', 'alcool', 3, 9, 'demo-soiree-qui-derape', 'debut.jpg'],
            'anxiete' => [HistoireAnxieteSeeder::class, 'demo_veille_du_partiel', 'anxiete', 4, 12, 'demo-veille-du-partiel', 'pause.svg'],
            'harcelement' => [HistoireHarcelementSeeder::class, 'demo_groupe_de_td', 'harcelement', 4, 12, 'demo-groupe-de-td', 'prive.svg'],
        ];
    }

    /**
     * @param  class-string<HistoireDemoSeeder>  $seeder
     */
    #[DataProvider('histoires')]
    public function test_le_seeder_est_idempotent(string $seeder, string $ref, string $sousTheme, int $nbScenes, int $nbChoix): void
    {
        $this->seed($seeder);
        $this->seed($seeder);

        $histoire = Histoire::where('ref', $ref)->sole();

        $this->assertCount($nbScenes, $histoire->scenes);
        $this->assertSame($nbChoix, $histoire->scenes->sum(fn ($scene) => $scene->choix()->count()));
        $this->assertSame([$sousTheme], $histoire->sousThemes->pluck('ref')->all());
    }

    /**
     * @param  class-string<HistoireDemoSeeder>  $seeder
     */
    #[DataProvider('histoires')]
    public function test_l_histoire_est_servie_avec_une_illustration_par_scene(string $seeder, string $ref, string $sousTheme, int $nbScenes): void
    {
        $this->seed($seeder);

        $response = $this->getJson("/api/histoires/{$ref}")
            ->assertOk()
            ->assertJsonCount($nbScenes, 'data.scenes');

        foreach ($response->json('data.scenes') as $scene) {
            $this->assertSame('image', $scene['media']['type']);
            $this->assertNotNull($scene['media']['url']);
        }
    }

    /**
     * @param  class-string<HistoireDemoSeeder>  $seeder
     */
    #[DataProvider('histoires')]
    public function test_un_fichier_source_modifie_est_recopie(string $seeder, string $ref, string $sousTheme, int $nbScenes, int $nbChoix, string $dossier, string $fichier): void
    {
        $chemin = "medias/image/{$dossier}/{$fichier}";
        Storage::disk('public')->put($chemin, 'ancienne version');

        $this->seed($seeder);

        $this->assertSame(
            file_get_contents(database_path("seeders/data/images/{$dossier}/{$fichier}")),
            Storage::disk('public')->get($chemin),
        );
    }
}
