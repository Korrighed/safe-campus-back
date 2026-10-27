<?php

namespace Tests\Feature;

use App\Enums\EtatHistoire;
use App\Enums\UserRole;
use App\Models\Histoire;
use App\Models\User;
use Illuminate\Log\Logger;
use Illuminate\Support\Facades\Log;
use Monolog\Handler\TestHandler;
use Monolog\Logger as MonologLogger;
use Monolog\LogRecord;
use Tests\TestCase;

class AuditHistoireTest extends TestCase
{
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

    /**
     * @return list<LogRecord>
     */
    private function lignes(string $action): array
    {
        return array_values(array_filter(
            $this->handler->getRecords(),
            fn (LogRecord $record): bool => $record->message === $action,
        ));
    }

    /**
     * Contextes de toutes les lignes, concatenes en JSON. Non echappe pour
     * qu'une recherche de texte accentue ou contenant un slash reste fiable.
     */
    private function json(): string
    {
        return implode('', array_map(
            fn (LogRecord $record): string => json_encode(
                $record->context,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
            ),
            $this->handler->getRecords(),
        ));
    }

    public function test_un_changement_d_etat_est_trace_avec_de_et_vers(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Relecteur]));
        $histoire = Histoire::factory()->create();
        $this->handler->clear();

        $histoire->update(['etat' => EtatHistoire::Relecture]);

        $this->assertCount(1, $this->lignes('Histoire.etat_change'));
        $ligne = $this->ligne('Histoire.etat_change');
        $this->assertNotNull($ligne);
        $this->assertSame('brouillon', $ligne->context['de']);
        $this->assertSame('relecture', $ligne->context['vers']);
        $this->assertSame('relecteur', $ligne->context['auteur']['role']);
        $this->assertSame('Histoire', $ligne->context['cible']['type']);
    }

    public function test_une_publication_depuis_la_relecture_est_tracee_avec_son_auteur(): void
    {
        $relecteur = User::factory()->create(['role' => UserRole::Relecteur]);
        $this->actingAs($relecteur);
        $histoire = Histoire::factory()->create(['etat' => EtatHistoire::Relecture]);
        $this->handler->clear();

        $histoire->update(['etat' => EtatHistoire::Publie]);

        $ligne = $this->ligne('Histoire.etat_change');
        $this->assertNotNull($ligne);
        $this->assertSame('relecture', $ligne->context['de']);
        $this->assertSame('publie', $ligne->context['vers']);
        $this->assertSame($relecteur->email, $ligne->context['auteur']['email']);
        $this->assertSame('relecteur', $ligne->context['auteur']['role']);
    }

    public function test_modifier_le_titre_ne_produit_pas_d_evenement_d_etat(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Relecteur]));
        $histoire = Histoire::factory()->create();
        $this->handler->clear();

        $histoire->update(['titre' => 'Autre titre']);

        // Garde-fou : prouve que l'observer a tourne, donc que le null ci-dessous a un sens.
        $this->assertNotNull($this->ligne('Histoire.modifie'));
        $this->assertNull($this->ligne('Histoire.etat_change'));
    }

    public function test_le_changement_d_etat_garde_aussi_son_histoire_modifie(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Relecteur]));
        $histoire = Histoire::factory()->create();
        $this->handler->clear();

        $histoire->update(['etat' => EtatHistoire::Relecture]);

        $ligne = $this->ligne('Histoire.modifie');
        $this->assertNotNull($ligne);
        $this->assertSame('relecture', $ligne->context['apres']['etat']);
    }
}
