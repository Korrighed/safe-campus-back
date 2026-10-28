<?php

namespace Tests\Feature\Database;

use App\Models\Histoire;
use Database\Seeders\HistoireAnxieteSeeder;
use Database\Seeders\HistoireBurnOutSeeder;
use Database\Seeders\HistoireDemoSeeder;
use Database\Seeders\HistoireDepressionSeeder;
use Database\Seeders\HistoireDrogueSeeder;
use Database\Seeders\HistoireHarcelementSeeder;
use Database\Seeders\HistoireSeeder;
use Database\Seeders\HistoireTabacSeeder;
use Database\Seeders\HistoireViolencesSexistesSeeder;
use Database\Seeders\HistoireViolencesSexuellesSeeder;
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
            'tabac' => [HistoireTabacSeeder::class, 'demo_pause_clope', 'tabac', 4, 12, 'demo-pause-clope', 'envie.svg'],
            'drogue' => [HistoireDrogueSeeder::class, 'demo_joint_qui_tourne', 'drogue', 4, 12, 'demo-joint-qui-tourne', 'coloc.svg'],
            'depression' => [HistoireDepressionSeeder::class, 'demo_volets_fermes', 'depression', 4, 12, 'demo-volets-fermes', 'absences.svg'],
            'burn_out' => [HistoireBurnOutSeeder::class, 'demo_tout_tenir', 'burn_out', 4, 12, 'demo-tout-tenir', 'agenda.svg'],
            'violences_sexistes' => [HistoireViolencesSexistesSeeder::class, 'demo_juste_une_blague', 'violences_sexistes', 4, 12, 'demo-juste-une-blague', 'soutien.svg'],
            'violences_sexuelles' => [HistoireViolencesSexuellesSeeder::class, 'demo_retour_de_soiree', 'violences_sexuelles', 4, 12, 'demo-retour-de-soiree', 'lendemain.svg'],
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
