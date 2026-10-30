<?php

namespace Tests\Feature;

use App\Enums\EtatHistoire;
use App\Enums\UserRole;
use App\Filament\Resources\HistoireResource\Pages\CreateHistoire;
use App\Filament\Resources\HistoireResource\Pages\EditHistoire;
use App\Filament\Resources\HistoireResource\Pages\ListHistoires;
use App\Filament\Resources\HistoireResource\Pages\ScenesHistoire;
use App\Models\Choix;
use App\Models\Histoire;
use App\Models\Scene;
use App\Models\SousTheme;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Log\Logger;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;
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
    private function contextesJson(): string
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
        $this->assertSame($relecteur->id, $ligne->context['auteur']['id']);
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

    /**
     * Histoire de $nombre scenes, la premiere en depart.
     *
     * @return array{0: Histoire, 1: list<Scene>}
     */
    private function histoireAvecScenes(User $auteur, int $nombre): array
    {
        $histoire = Histoire::factory()->pour($auteur)->create();
        $scenes = [];

        foreach (range(1, $nombre) as $ordre) {
            $scene = Scene::factory()->create();
            $histoire->scenes()->attach($scene->id, ['est_initiale' => $ordre === 1, 'ordre' => $ordre]);
            $scenes[] = $scene;
        }

        return [$histoire, $scenes];
    }

    public function test_ajouter_une_scene_est_trace(): void
    {
        $redacteur = User::factory()->create(['role' => UserRole::Redacteur]);
        $this->actingAs($redacteur);
        $histoire = Histoire::factory()->pour($redacteur)->create();
        $this->handler->clear();

        Livewire::test(ScenesHistoire::class, ['record' => $histoire->getKey()])
            ->call('nouvelleScene')
            ->fillForm(['titre' => 'Titre secret', 'dialogue_text' => 'Texte secret'])
            ->call('enregistrer')
            ->assertHasNoFormErrors();

        $ligne = $this->ligne('Histoire.scene_ajoutee');
        $this->assertNotNull($ligne);
        $this->assertSame($histoire->scenes()->sole()->id, $ligne->context['scene_id']);
        $this->assertStringNotContainsString('Titre secret', $this->contextesJson());
        $this->assertStringNotContainsString('Texte secret', $this->contextesJson());
    }

    public function test_reutiliser_une_scene_est_trace(): void
    {
        $redacteur = User::factory()->create(['role' => UserRole::Redacteur]);
        $this->actingAs($redacteur);
        $histoire = Histoire::factory()->pour($redacteur)->create();
        $autre = Histoire::factory()->pour($redacteur)->create();
        $partagee = Scene::factory()->create();
        $autre->scenes()->attach($partagee->id, ['est_initiale' => true, 'ordre' => 1]);
        $this->handler->clear();

        Livewire::test(ScenesHistoire::class, ['record' => $histoire->getKey()])
            ->set('reutilisationOuverte', true)
            ->set('sceneAReutiliser', $partagee->id)
            ->call('reutiliser');

        $ligne = $this->ligne('Histoire.scene_ajoutee');
        $this->assertNotNull($ligne);
        $this->assertSame($partagee->id, $ligne->context['scene_id']);
        $this->assertTrue($ligne->context['reutilisee']);
    }

    public function test_retirer_une_scene_est_trace(): void
    {
        $redacteur = User::factory()->create(['role' => UserRole::Redacteur]);
        $this->actingAs($redacteur);
        [$histoire, $scenes] = $this->histoireAvecScenes($redacteur, 1);
        $this->handler->clear();

        Livewire::test(ScenesHistoire::class, ['record' => $histoire->getKey()])
            ->callAction('retirerScene', arguments: ['scene' => $scenes[0]->id]);

        $ligne = $this->ligne('Histoire.scene_retiree');
        $this->assertNotNull($ligne);
        $this->assertSame($scenes[0]->id, $ligne->context['scene_id']);
    }

    public function test_definir_le_depart_est_trace(): void
    {
        $redacteur = User::factory()->create(['role' => UserRole::Redacteur]);
        $this->actingAs($redacteur);
        [$histoire, $scenes] = $this->histoireAvecScenes($redacteur, 2);
        $this->handler->clear();

        Livewire::test(ScenesHistoire::class, ['record' => $histoire->getKey()])
            ->call('definirDepart', $scenes[1]->id);

        $ligne = $this->ligne('Histoire.depart_defini');
        $this->assertNotNull($ligne);
        $this->assertSame($scenes[1]->id, $ligne->context['scene_id']);
    }

    public function test_deplacer_une_scene_est_trace(): void
    {
        $redacteur = User::factory()->create(['role' => UserRole::Redacteur]);
        $this->actingAs($redacteur);
        [$histoire, $scenes] = $this->histoireAvecScenes($redacteur, 2);
        $this->handler->clear();

        Livewire::test(ScenesHistoire::class, ['record' => $histoire->getKey()])
            ->call('descendre', $scenes[0]->id);

        $ligne = $this->ligne('Histoire.scene_deplacee');
        $this->assertNotNull($ligne);
        $this->assertSame($scenes[0]->id, $ligne->context['scene_id']);
        $this->assertSame('bas', $ligne->context['sens']);
    }

    public function test_modifier_les_sous_themes_est_trace(): void
    {
        $redacteur = User::factory()->create(['role' => UserRole::Redacteur]);
        $this->actingAs($redacteur);
        $histoire = Histoire::factory()->pour($redacteur)->create(['etat' => EtatHistoire::Brouillon]);
        $a = SousTheme::factory()->create();
        $b = SousTheme::factory()->create();
        $this->handler->clear();

        Livewire::test(EditHistoire::class, ['record' => $histoire->getKey()])
            ->fillForm(['sousThemes' => [$a->id]])
            ->call('save')
            ->fillForm(['sousThemes' => [$b->id]])
            ->call('save');

        $lignes = $this->lignes('Histoire.sous_themes_modifies');
        $this->assertCount(2, $lignes);
        $this->assertSame([$b->id], $lignes[1]->context['ajoutes']);
        $this->assertSame([$a->id], $lignes[1]->context['retires']);
    }

    public function test_ouvrir_l_histoire_d_un_autre_auteur_est_trace(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Redacteur]));
        $histoireDeB = Histoire::factory()->pour(User::factory()->create(['role' => UserRole::Redacteur]))->create();
        $this->handler->clear();

        try {
            Livewire::test(EditHistoire::class, ['record' => $histoireDeB->getKey()]);
        } catch (ModelNotFoundException) {
            // Attendu : hors perimetre = 404. La trace doit exister malgre l'exception.
        }

        $ligne = $this->ligne('Histoire.acces_refuse');
        $this->assertNotNull($ligne);
        $this->assertSame('hors_perimetre', $ligne->context['motif']);
        $this->assertSame($histoireDeB->id, $ligne->context['cible']['id']);
    }

    public function test_une_page_infos_verrouillee_est_tracee(): void
    {
        $redacteur = User::factory()->create(['role' => UserRole::Redacteur]);
        $this->actingAs($redacteur);
        $histoire = Histoire::factory()->pour($redacteur)->create(['etat' => EtatHistoire::Relecture]);
        $this->handler->clear();

        Livewire::test(EditHistoire::class, ['record' => $histoire->getKey()])
            ->assertForbidden();

        $ligne = $this->ligne('Histoire.acces_refuse');
        $this->assertNotNull($ligne);
        $this->assertSame('droit_refuse', $ligne->context['motif']);
        $this->assertSame('update', $ligne->context['capacite']);
    }

    public function test_une_ecriture_de_scene_refusee_est_tracee(): void
    {
        $redacteur = User::factory()->create(['role' => UserRole::Redacteur]);
        $this->actingAs($redacteur);
        $histoire = Histoire::factory()->pour($redacteur)->create(['etat' => EtatHistoire::Relecture]);
        $this->handler->clear();

        Livewire::test(ScenesHistoire::class, ['record' => $histoire->getKey()])
            ->call('nouvelleScene')
            ->assertForbidden();

        $ligne = $this->ligne('Histoire.acces_refuse');
        $this->assertNotNull($ligne);
        $this->assertSame('update', $ligne->context['capacite']);
    }

    public function test_un_acces_autorise_ne_produit_pas_d_evenement(): void
    {
        $redacteur = User::factory()->create(['role' => UserRole::Redacteur]);
        $this->actingAs($redacteur);
        $histoire = Histoire::factory()->pour($redacteur)->create(['etat' => EtatHistoire::Brouillon]);
        $this->handler->clear();

        Livewire::test(EditHistoire::class, ['record' => $histoire->getKey()])
            ->assertSuccessful();
        Livewire::test(ScenesHistoire::class, ['record' => $histoire->getKey()])
            ->assertSuccessful();

        $this->assertSame([], $this->lignes('Histoire.acces_refuse'));
    }

    public function test_une_suppression_groupee_est_tracee_avec_les_supprimees_et_les_refusees(): void
    {
        $redacteur = User::factory()->create(['role' => UserRole::Redacteur]);
        $this->actingAs($redacteur);
        $brouillon = Histoire::factory()->pour($redacteur)->create(['etat' => EtatHistoire::Brouillon]);
        $relecture = Histoire::factory()->pour($redacteur)->create(['etat' => EtatHistoire::Relecture]);
        $this->handler->clear();

        Livewire::test(ListHistoires::class)
            ->callTableBulkAction('supprimer', [$brouillon, $relecture]);

        $ligne = $this->ligne('Histoire.suppression_groupee');
        $this->assertNotNull($ligne);
        $this->assertSame([$brouillon->id], $ligne->context['supprimees']);
        $this->assertSame([$relecture->id], $ligne->context['refusees']);
        $this->assertSame('redacteur', $ligne->context['auteur']['role']);

        $supprimees = $this->lignes('Histoire.supprime');
        $this->assertCount(1, $supprimees);
        $this->assertSame($brouillon->id, $supprimees[0]->context['cible']['id']);
    }

    public function test_une_suppression_groupee_ne_contient_aucun_titre(): void
    {
        $redacteur = User::factory()->create(['role' => UserRole::Redacteur]);
        $this->actingAs($redacteur);
        $brouillon = Histoire::factory()->pour($redacteur)->create(['titre' => 'Titre distinctif brouillon', 'etat' => EtatHistoire::Brouillon]);
        $relecture = Histoire::factory()->pour($redacteur)->create(['titre' => 'Titre distinctif relecture', 'etat' => EtatHistoire::Relecture]);
        $this->handler->clear();

        Livewire::test(ListHistoires::class)
            ->callTableBulkAction('supprimer', [$brouillon, $relecture]);

        $ligne = $this->ligne('Histoire.suppression_groupee');
        $this->assertNotNull($ligne);

        // Seule la ligne groupee : contextesJson() concatene toutes les lignes, dont Histoire.supprime (titre non masque, cahier des charges § 4.1).
        $contenu = json_encode($ligne->context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('Titre distinctif brouillon', $contenu);
        $this->assertStringNotContainsString('Titre distinctif relecture', $contenu);
    }

    public function test_creer_une_histoire_trace_la_premiere_scene_et_les_sous_themes(): void
    {
        $redacteur = User::factory()->create(['role' => UserRole::Redacteur]);
        $this->actingAs($redacteur);
        $sousTheme = SousTheme::factory()->create();
        $this->handler->clear();

        Livewire::test(CreateHistoire::class)
            ->fillForm([
                'titre' => 'Histoire tracee',
                'sousThemes' => [$sousTheme->id],
                'premiere_scene' => ['titre' => 'Titre secret', 'dialogue_text' => 'Texte secret'],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $histoire = Histoire::query()->where('ref', 'histoire_tracee')->sole();

        $scene = $this->ligne('Histoire.scene_ajoutee');
        $this->assertNotNull($scene);
        $this->assertSame($histoire->sceneInitiale()?->id, $scene->context['scene_id']);
        $this->assertTrue($scene->context['initiale']);

        $sousThemes = $this->lignes('Histoire.sous_themes_modifies');
        $this->assertCount(1, $sousThemes);
        $this->assertSame([$sousTheme->id], $sousThemes[0]->context['ajoutes']);
        $this->assertSame([], $sousThemes[0]->context['retires']);

        $this->assertStringNotContainsString('Titre secret', $this->contextesJson());
        $this->assertStringNotContainsString('Texte secret', $this->contextesJson());
    }

    public function test_supprimer_un_choix_en_editant_une_scene_est_trace(): void
    {
        $redacteur = User::factory()->create(['role' => UserRole::Redacteur]);
        $this->actingAs($redacteur);
        [$histoire, $scenes] = $this->histoireAvecScenes($redacteur, 2);
        $garde = Choix::factory()->create(['scene_id' => $scenes[0]->id, 'next_scene_id' => $scenes[1]->id, 'text_choix' => 'Entrer']);
        $retire = Choix::factory()->favorable()->create(['scene_id' => $scenes[0]->id, 'text_choix' => 'Rentrer']);
        $this->handler->clear();

        Livewire::test(ScenesHistoire::class, ['record' => $histoire->getKey()])
            ->call('modifier', $scenes[0]->id)
            ->set('donnees.choix', [
                "choix-{$garde->id}" => ['id' => $garde->id, 'text_choix' => 'Entrer', 'suite' => 'continuer', 'next_scene_id' => $scenes[1]->id],
            ])
            ->call('enregistrer')
            ->assertHasNoFormErrors();

        $lignes = $this->lignes('Choix.supprime');
        $this->assertCount(1, $lignes);
        $this->assertSame($retire->id, $lignes[0]->context['cible']['id']);
    }
}
