<?php

namespace Tests\Feature\Database;

use App\Models\Histoire;
use Database\Seeders\HistoireAnxieteSeeder;
use Database\Seeders\RedacteurDemoSeeder;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HistoireAnxieteSeederTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->seed(RedacteurDemoSeeder::class);
    }

    public function test_le_seeder_est_idempotent(): void
    {
        $this->seed(HistoireAnxieteSeeder::class);
        $this->seed(HistoireAnxieteSeeder::class);

        $histoire = Histoire::where('ref', 'demo_veille_du_partiel')->sole();

        $this->assertCount(4, $histoire->scenes);
        $this->assertSame(12, $histoire->scenes->sum(fn ($scene) => $scene->choix()->count()));
        $this->assertSame(['anxiete'], $histoire->sousThemes->pluck('ref')->all());
    }

    public function test_l_histoire_est_servie_avec_une_illustration_svg_par_scene(): void
    {
        $this->seed(HistoireAnxieteSeeder::class);

        $response = $this->getJson('/api/histoires/demo_veille_du_partiel')
            ->assertOk()
            ->assertJsonCount(4, 'data.scenes');

        foreach ($response->json('data.scenes') as $scene) {
            $this->assertSame('image', $scene['media']['type']);
            $this->assertStringEndsWith('.svg', $scene['media']['url']);
        }

        Storage::disk('public')->assertExists('medias/image/demo-veille-du-partiel/veille.svg');
    }

    public function test_un_fichier_source_modifie_est_recopie(): void
    {
        $chemin = 'medias/image/demo-veille-du-partiel/pause.svg';
        Storage::disk('public')->put($chemin, 'ancienne version');

        $this->seed(HistoireAnxieteSeeder::class);

        $this->assertSame(
            file_get_contents(database_path('seeders/data/images/demo-veille-du-partiel/pause.svg')),
            Storage::disk('public')->get($chemin),
        );
    }
}
