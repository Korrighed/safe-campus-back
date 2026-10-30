<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * En-tetes de securite, CORS et limite de debit du portillon.
 */
class SecuriteHttpTest extends TestCase
{
    public function test_l_api_pose_les_en_tetes_de_securite(): void
    {
        $this->getJson('/api/themes')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()')
            ->assertHeader('Content-Security-Policy', "default-src 'none'; frame-ancestors 'none'")
            ->assertHeaderMissing('Strict-Transport-Security');
    }

    public function test_le_panneau_admin_interdit_d_etre_encadre(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertHeader('Content-Security-Policy', "frame-ancestors 'none'")
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_hsts_n_est_pose_qu_en_https(): void
    {
        $this->get('https://localhost/api/themes')
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000');
    }

    public function test_une_origine_autorisee_recoit_les_en_tetes_cors(): void
    {
        config(['cors.allowed_origins' => ['https://front.test']]);

        $this->call('OPTIONS', '/api/themes', [], [], [], [
            'HTTP_ORIGIN' => 'https://front.test',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
        ])->assertHeader('Access-Control-Allow-Origin', 'https://front.test');

        $this->getJson('/api/themes', ['Origin' => 'https://front.test'])
            ->assertHeader('Access-Control-Allow-Origin', 'https://front.test');
    }

    public function test_une_origine_inconnue_ne_recoit_aucune_autorisation_cors(): void
    {
        // Plusieurs origines : le serveur repond dynamiquement, une origine inconnue
        // ne recoit aucun en-tete d'autorisation.
        config(['cors.allowed_origins' => ['https://front.test', 'https://demo.test']]);

        $this->call('OPTIONS', '/api/themes', [], [], [], [
            'HTTP_ORIGIN' => 'https://autre-site.test',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
        ])->assertHeaderMissing('Access-Control-Allow-Origin');

        $this->getJson('/api/themes', ['Origin' => 'https://autre-site.test'])
            ->assertHeaderMissing('Access-Control-Allow-Origin');
    }

    public function test_avec_une_seule_origine_le_navigateur_rejette_les_autres(): void
    {
        // Une seule origine : l'en-tete porte toujours cette origine (comportement
        // de stack-cors) ; le navigateur refuse toute origine differente.
        config(['cors.allowed_origins' => ['https://front.test']]);

        $reponse = $this->getJson('/api/themes', ['Origin' => 'https://autre-site.test']);

        $reponse->assertHeader('Access-Control-Allow-Origin', 'https://front.test');
        $this->assertNotSame('*', $reponse->headers->get('Access-Control-Allow-Origin'));
    }

    public function test_le_login_visiteur_est_limite_en_tentatives(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->postJson('/api/login', ['password' => 'mauvais-mot-de-passe'])->assertUnauthorized();
        }

        $this->postJson('/api/login', ['password' => 'mauvais-mot-de-passe'])->assertStatus(429);
    }
}
