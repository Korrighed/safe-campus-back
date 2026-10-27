<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\Auth\Login;
use App\Models\Choix;
use App\Models\Scene;
use App\Models\SousTheme;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Log\Logger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\TestHandler;
use Monolog\Logger as MonologLogger;
use Monolog\LogRecord;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    private const MOT_DE_PASSE = 'Campus-Securise-2026!';

    private TestHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        // Canal audit redirige en memoire : rien n'est ecrit dans storage/logs.
        $this->handler = new TestHandler;
        /** @var Logger $canal */
        $canal = Log::channel('audit');
        /** @var MonologLogger $monolog */
        $monolog = $canal->getLogger();
        $monolog->setHandlers([$this->handler]);
    }

    private function ligne(string $action): ?LogRecord
    {
        foreach ($this->handler->getRecords() as $record) {
            if ($record->message === $action) {
                return $record;
            }
        }

        return null;
    }

    public function test_le_canal_audit_conserve_un_an_un_fichier_par_jour(): void
    {
        $canal = require config_path('logging.php');

        $this->assertSame('daily', $canal['channels']['audit']['driver']);
        $this->assertSame(365, $canal['channels']['audit']['days']);
        // Independant de LOG_LEVEL (error en production).
        $this->assertSame('info', $canal['channels']['audit']['level']);
        // Une entree par ligne, meme avec des retours a la ligne dans les valeurs.
        $this->assertSame(JsonFormatter::class, $canal['channels']['audit']['formatter']);
    }

    public function test_une_creation_est_tracee_avec_son_auteur(): void
    {
        $webmaster = User::factory()->create(['role' => UserRole::Webmaster]);
        $this->actingAs($webmaster);

        Theme::create(['ref' => 'audit_'.uniqid(), 'libelle' => 'Thème audité', 'ordre' => 0]);

        $ligne = $this->ligne('Theme.cree');
        $this->assertNotNull($ligne);
        $this->assertSame($webmaster->email, $ligne->context['auteur']['email']);
        $this->assertSame('Thème audité', $ligne->context['valeurs']['libelle']);
    }

    public function test_une_action_annulee_n_est_pas_tracee(): void
    {
        try {
            DB::transaction(function (): void {
                User::factory()->create(['email' => 'annule.'.uniqid().'@test.nc']);

                throw new \RuntimeException('echec simule');
            });
        } catch (\RuntimeException) {
        }

        $this->assertNull($this->ligne('User.cree'));
    }

    public function test_une_modification_trace_avant_et_apres(): void
    {
        $user = User::factory()->create(['role' => null]);
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $user->update(['role' => UserRole::Redacteur]);

        $ligne = $this->ligne('User.modifie');
        $this->assertNotNull($ligne);
        $this->assertSame(['role' => null], $ligne->context['avant']);
        $this->assertSame(['role' => 'redacteur'], $ligne->context['apres']);
        $this->assertSame($user->email, $ligne->context['cible']['libelle']);
    }

    public function test_une_suppression_garde_un_libelle_lisible(): void
    {
        $user = User::factory()->create(['role' => UserRole::Webmaster]);

        $user->delete();

        $ligne = $this->ligne('User.supprime');
        $this->assertNotNull($ligne);
        $this->assertSame($user->email, $ligne->context['cible']['libelle']);
        $this->assertSame('webmaster', $ligne->context['valeurs']['role']);
    }

    public function test_aucun_mot_de_passe_n_apparait_dans_les_logs(): void
    {
        $user = User::factory()->create(['password' => self::MOT_DE_PASSE]);
        $user->update(['password' => 'Autre-Mot-De-Passe-2026!', 'nom' => 'Nouveau']);

        foreach ($this->handler->getRecords() as $record) {
            $json = json_encode($record->context);
            $this->assertStringNotContainsString('password', $json);
            $this->assertStringNotContainsString('$2y$', $json);
            $this->assertStringNotContainsString(self::MOT_DE_PASSE, $json);
        }

        $this->assertSame(['nom' => 'Nouveau'], $this->ligne('User.modifie')->context['apres']);
    }

    public function test_un_changement_de_remember_token_seul_n_est_pas_trace(): void
    {
        $user = User::factory()->create();
        $this->handler->clear();

        $user->update(['remember_token' => 'nouveau-jeton']);

        $this->assertNull($this->ligne('User.modifie'));
    }

    public function test_un_texte_long_est_tronque(): void
    {
        Theme::create(['ref' => 'audit_'.uniqid(), 'libelle' => 'Long', 'resume' => str_repeat('a', 5000), 'ordre' => 0]);

        $resume = $this->ligne('Theme.cree')->context['valeurs']['resume'];

        $this->assertLessThan(300, mb_strlen($resume));
        $this->assertStringContainsString('5000 caractères', $resume);
    }

    public function test_connexion_et_echec_sont_traces_sans_mot_de_passe(): void
    {
        $user = User::factory()->create(['role' => UserRole::Webmaster, 'password' => self::MOT_DE_PASSE]);

        Livewire::test(Login::class)
            ->fillForm(['email' => $user->email, 'password' => 'Mauvais-Mot-De-Passe!'])
            ->call('authenticate');

        $echec = $this->ligne('auth.echec');
        $this->assertNotNull($echec);
        $this->assertSame($user->email, $echec->context['email_tente']);
        $this->assertStringNotContainsString('Mauvais-Mot-De-Passe', json_encode($echec->context));

        Livewire::test(Login::class)
            ->fillForm(['email' => $user->email, 'password' => self::MOT_DE_PASSE])
            ->call('authenticate');

        $this->assertSame($user->email, $this->ligne('auth.connexion')?->context['cible']['libelle']);
    }

    public function test_le_contenu_d_une_scene_n_est_jamais_journalise(): void
    {
        Scene::factory()->create(['titre' => 'Titre confidentiel', 'dialogue_text' => 'Dialogue confidentiel']);

        foreach ($this->handler->getRecords() as $record) {
            $json = json_encode($record->context);
            $this->assertStringNotContainsString('Titre confidentiel', $json);
            $this->assertStringNotContainsString('Dialogue confidentiel', $json);
        }

        $ligne = $this->ligne('Scene.cree');
        $this->assertNotNull($ligne);
        $this->assertArrayNotHasKey('libelle', $ligne->context['cible']);
    }

    public function test_modifier_un_dialogue_trace_le_nom_du_champ_sans_le_texte(): void
    {
        $scene = Scene::factory()->create(['titre' => 'Titre confidentiel', 'dialogue_text' => 'Dialogue confidentiel']);
        $this->handler->clear();

        $scene->update(['dialogue_text' => 'Nouveau texte confidentiel']);

        $ligne = $this->ligne('Scene.modifie');
        $this->assertNotNull($ligne);
        $this->assertSame(['dialogue_text'], $ligne->context['champs_modifies']);
        $this->assertArrayNotHasKey('avant', $ligne->context);
        $this->assertArrayNotHasKey('apres', $ligne->context);

        foreach ($this->handler->getRecords() as $record) {
            $this->assertStringNotContainsString('confidentiel', json_encode($record->context));
        }
    }

    public function test_le_texte_d_un_choix_n_est_jamais_journalise(): void
    {
        $choix = Choix::factory()->create(['text_choix' => 'Choix confidentiel']);

        $choix->update(['text_choix' => 'Nouveau choix confidentiel']);

        $ligne = $this->ligne('Choix.modifie');
        $this->assertNotNull($ligne);
        $this->assertSame(['text_choix'], $ligne->context['champs_modifies']);

        foreach ($this->handler->getRecords() as $record) {
            $this->assertStringNotContainsString('confidentiel', json_encode($record->context));
        }
    }

    public function test_un_champ_non_confidentiel_d_une_scene_reste_trace(): void
    {
        $scene = Scene::factory()->create();
        $sousTheme = SousTheme::factory()->create();
        $this->handler->clear();

        $scene->update(['dialogue_text' => 'Nouveau texte confidentiel', 'sous_theme_id' => $sousTheme->id]);

        $ligne = $this->ligne('Scene.modifie');
        $this->assertNotNull($ligne);
        $this->assertSame($sousTheme->id, $ligne->context['apres']['sous_theme_id']);
        $this->assertArrayNotHasKey('dialogue_text', $ligne->context['apres']);
        $this->assertEqualsCanonicalizing(['dialogue_text', 'sous_theme_id'], $ligne->context['champs_modifies']);
    }
}
