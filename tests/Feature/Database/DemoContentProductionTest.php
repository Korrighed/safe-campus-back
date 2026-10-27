<?php

namespace Tests\Feature\Database;

use App\Models\Histoire;
use App\Models\User;
use Database\Seeders\HistoireSeeder;
use Database\Seeders\RedacteurDemoSeeder;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Les seeders de demo sont ignores en production, sauf DEMO_CONTENT=true
 * (demo publique : docker/8.4/start-prod les rejoue a chaque demarrage).
 */
class DemoContentProductionTest extends TestCase
{
    private const EMAIL = 'redacteur-demo@example.test';

    private const IMAGE = 'medias/image/demo-soiree-qui-derape/debut.jpg';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->app->detectEnvironment(fn () => 'production');
        config([
            'redacteur_demo.email' => self::EMAIL,
            'redacteur_demo.password' => 'Un-mot-de-passe-long-et-aleatoire-9!',
        ]);
    }

    /**
     * Appel direct des seeders : `$this->seed()` passe par db:seed, qui refuse de
     * tourner en production sans --force et fausserait le test.
     */
    private function lancer(): void
    {
        (new RedacteurDemoSeeder)->run();
        (new HistoireSeeder)->run();
    }

    public function test_les_seeders_de_demo_sont_ignores_en_production_par_defaut(): void
    {
        config(['demo.content' => false]);
        $utilisateurs = User::count();
        $histoires = Histoire::count();

        $this->lancer();

        $this->assertSame($utilisateurs, User::count());
        $this->assertSame($histoires, Histoire::count());
        Storage::disk('public')->assertMissing(self::IMAGE);
    }

    public function test_demo_content_autorise_les_seeders_en_production(): void
    {
        config(['demo.content' => true]);

        $this->lancer();

        $this->assertDatabaseHas('users', ['email' => self::EMAIL, 'role' => 'redacteur']);
        $this->assertSame('publie', Histoire::where('ref', 'demo_soiree_qui_derape')->sole()->etat->value);
        Storage::disk('public')->assertExists(self::IMAGE);
    }

    public function test_demo_content_est_faux_par_defaut(): void
    {
        $this->assertFalse((bool) config('demo.content'));
    }
}
