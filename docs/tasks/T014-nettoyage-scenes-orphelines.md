# T014 — Nettoyer les scènes orphelines

Statut : fait
Story : hors lot initial (demande du porteur) · Exigences : aucune
Dépend de : T012
Parallélisable : oui (avec T015)
Exécutant : back

## Objectif

Supprimer une histoire supprime aussi ses scènes, à condition qu'aucune autre histoire ne les utilise. Une commande `scenes:purger-orphelines` supprime les scènes déjà orphelines en base. Les choix des scènes supprimées partent avec elles (clé étrangère en cascade).

## Constat de départ

- `histoire_scene` est en cascade vers `histoires` : supprimer une histoire détache ses scènes, sans les supprimer. Elles restent en base, invisibles et non réutilisables.
- `choix.scene_id` est en cascade vers `scenes`. `choix.next_scene_id` est en `nullOnDelete`.
- `Scene` est observée par `AuditObserver` : chaque suppression écrit `Scene.supprime` (sans contenu, voir T007).

## Fichiers à lire

- `app/Models/Histoire.php` — `booted()`.
- `app/Models/Scene.php` — relation `histoires()`.
- `app/Console/Commands/PurgerComptesInactifs.php` — modèle de commande (attributs `#[Signature]`, `#[Description]`, option `--simulation`).
- `app/Services/SuppressionCompteService.php` — style d'un service de purge.
- `tests/Feature/PurgeComptesInactifsTest.php` — style de test de commande.
- `docs/deploiement.md` — section des commandes d'exploitation.
- `docs/schema_bd.md` — § « Administration des comptes » et § Rôles (affirmations sur la suppression).
- `resources/views/filament/widgets/guide-relecteur.blade.php` — puce sur la suppression.

## Fichiers cibles

| Fichier | Action | Ce qui change |
|---|---|---|
| `app/Services/NettoyageScenesService.php` | créer | Logique de suppression des scènes orphelines. |
| `app/Models/Histoire.php` | modifier | Hooks `deleting` et `deleted`. |
| `app/Console/Commands/PurgerScenesOrphelines.php` | créer | Commande `scenes:purger-orphelines`. |
| `tests/Feature/NettoyageScenesTest.php` | créer | Tests du service, du hook et de la commande. |
| `docs/deploiement.md`, `docs/schema_bd.md` | modifier | Commande et nouveau comportement de suppression. |
| `resources/views/filament/widgets/guide-relecteur.blade.php` | modifier | Puce sur la suppression d'une histoire. |

## Étapes

1. Créer `app/Services/NettoyageScenesService.php` :

```php
<?php

namespace App\Services;

use App\Models\Scene;

/**
 * Supprime les scenes qu'aucune histoire n'utilise plus. Les choix partent
 * avec elles (cascade) ; chaque suppression passe par le modele et apparait
 * donc dans l'audit (Scene.supprime, sans contenu).
 */
class NettoyageScenesService
{
    /**
     * Scenes creees depuis moins longtemps que ce delai sont epargnees par la
     * commande : une scene en cours de creation ne doit jamais etre purgee.
     */
    public const DELAI_MINIMUM_HEURES = 24;

    /**
     * Supprime, parmi $ids, les scenes sans histoire.
     *
     * @param  list<int>  $ids
     * @return list<int> identifiants supprimes
     */
    public function supprimerOrphelinesParmi(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $supprimees = [];

        Scene::query()
            ->whereIn('id', $ids)
            ->whereDoesntHave('histoires')
            ->get()
            ->each(function (Scene $scene) use (&$supprimees): void {
                $supprimees[] = $scene->getKey();
                $scene->delete();
            });

        return $supprimees;
    }

    /**
     * Toutes les scenes orphelines creees il y a plus de DELAI_MINIMUM_HEURES.
     *
     * @return list<int> identifiants concernes
     */
    public function purgerOrphelines(bool $simulation = false): array
    {
        $scenes = Scene::query()
            ->whereDoesntHave('histoires')
            ->where('created_at', '<', now()->subHours(self::DELAI_MINIMUM_HEURES))
            ->get();

        if (! $simulation) {
            $scenes->each->delete();
        }

        return $scenes->modelKeys();
    }
}
```

2. `Histoire.php` : dans `booted()`, après le hook `creating`, ajouter deux hooks. Ajouter une propriété statique privée pour mémoriser les scènes avant la suppression (le pivot est effacé par cascade SQL au moment du delete) :

```php
    /**
     * Scenes de chaque histoire en cours de suppression, memorisees avant que
     * la cascade SQL ne detache le pivot. Cle : id de l'histoire.
     *
     * @var array<int, list<int>>
     */
    private static array $scenesANettoyer = [];
```

   et dans `booted()` :

```php
        static::deleting(function (Histoire $histoire): void {
            self::$scenesANettoyer[$histoire->getKey()] = $histoire->scenes()->pluck('scenes.id')->map(fn ($id) => (int) $id)->all();
        });

        static::deleted(function (Histoire $histoire): void {
            $ids = self::$scenesANettoyer[$histoire->getKey()] ?? [];
            unset(self::$scenesANettoyer[$histoire->getKey()]);

            app(NettoyageScenesService::class)->supprimerOrphelinesParmi($ids);
        });
```

   Importer `App\Services\NettoyageScenesService`. Ne pas modifier le hook `creating`.
3. Créer `app/Console/Commands/PurgerScenesOrphelines.php`, calqué sur `PurgerComptesInactifs` : `#[Signature('scenes:purger-orphelines {--simulation : Liste les scenes concernees sans rien supprimer}')]`, `#[Description('Supprime les scenes qu\'aucune histoire n\'utilise, creees il y a plus de '.NettoyageScenesService::DELAI_MINIMUM_HEURES.' heures')]`. `handle(NettoyageScenesService $service): int` : appelle `purgerOrphelines($this->option('simulation'))`, affiche une ligne `A supprimer : scene #id` ou `Supprimee : scene #id` par scène (jamais le titre ni le texte), puis `$this->info(count($ids).' scene(s) concernee(s).')`, retourne `self::SUCCESS`. Ne PAS ajouter la commande à `routes/console.php` (pas de planification automatique).
4. Créer `tests/Feature/NettoyageScenesTest.php` (classe `Tests\Feature\NettoyageScenesTest extends TestCase`) :
   - `test_supprimer_une_histoire_supprime_ses_scenes_orphelines` : histoire avec deux scènes ; `$histoire->delete()` ; les deux scènes et leurs choix (créés par `Choix::factory()->create(['scene_id' => ...])`) n'existent plus.
   - `test_une_scene_partagee_avec_une_autre_histoire_est_conservee` : une scène dans deux histoires ; supprimer la première ; la scène existe encore ; une scène exclusive de l'histoire supprimée a disparu.
   - `test_le_choix_d_une_scene_conservee_vers_une_scene_supprimee_perd_sa_cible` : scène partagée S1 avec un choix `next_scene_id` vers S2 (exclusive de l'histoire supprimée) ; après suppression, `S1` existe et le choix a `next_scene_id === null`.
   - `test_la_commande_supprime_les_scenes_orphelines_anciennes` : `Scene::factory()->create(['created_at' => now()->subDays(3)])` sans histoire ; `$this->artisan('scenes:purger-orphelines')->assertSuccessful()` ; la scène n'existe plus.
   - `test_la_commande_epargne_les_scenes_recentes_et_rattachees` : une scène orpheline créée à l'instant, une scène ancienne rattachée à une histoire ; elles existent après la commande.
   - `test_la_simulation_ne_supprime_rien` : scène orpheline ancienne ; `--simulation` ; elle existe encore.
5. `docs/deploiement.md` : ajouter, dans la section des commandes d'exploitation, `scenes:purger-orphelines [--simulation]` : supprime les scènes sans histoire créées il y a plus de 24 heures ; non planifiée ; à lancer à la main. `docs/schema_bd.md` : corriger toute phrase disant que supprimer une histoire laisse ses scènes en base ; la règle est : les scènes qu'aucune autre histoire n'utilise sont supprimées avec l'histoire, leurs choix avec elles. Chercher avec `grep -n "scène" docs/schema_bd.md | grep -iE "supprim|orphelin|invisible"`.
6. Guide relecteur (`guide-relecteur.blade.php`) : la puce sur la suppression doit dire que les scènes et choix de l'histoire sont supprimés avec elle, sauf les scènes utilisées par une autre histoire.
7. Ne pas modifier `docs/securite-rgpd.md` (un autre agent y travaille).

## Critères d'acceptation

- [ ] AC-01 : Étant donné une histoire de deux scènes exclusives, quand on la supprime, alors les deux scènes et leurs choix n'existent plus.
- [ ] AC-02 : Étant donné une scène partagée, quand on supprime l'une des histoires, alors la scène reste.
- [ ] AC-03 : `php artisan scenes:purger-orphelines` supprime les scènes sans histoire de plus de 24 heures et épargne les autres. `--simulation` ne supprime rien.
- [ ] AC-04 : La sortie de la commande ne contient aucun titre ni texte de scène.
- [ ] AC-05 : Aucun test existant ne casse : `test_supprimer_une_scene_la_supprime_definitivement` et les tests de suppression d'histoire restent valides.

## Vérification

Aucune exécution pendant le lot (consigne). Commandes à lancer en fin de lot :

```bash
docker compose exec sc_back vendor/bin/pint --test
docker compose exec sc_back vendor/bin/phpstan analyse --memory-limit=512M
docker compose exec sc_back php artisan test --filter="NettoyageScenesTest|HistoireResourceTest|HistoireScenesTest"
docker compose exec sc_back php artisan scenes:purger-orphelines --simulation
```

Parcours navigateur : relecteur, supprimer une histoire de test depuis la page Infos, vérifier que ses scènes ne sont plus proposées à la réutilisation.

## Hors périmètre

- Pas de planification automatique de la commande.
- Pas de suppression des médias liés aux scènes.
- Ne pas modifier `SuppressionCompteService`.

## Notes d'exécution

- Exécutant : back, 2026-10-28. Branche vérifiée : `feature/role-relecteur`.
- Créés : `app/Services/NettoyageScenesService.php`, `app/Console/Commands/PurgerScenesOrphelines.php`, `tests/Feature/NettoyageScenesTest.php`. Modifiés : `app/Models/Histoire.php` (import, propriété `$scenesANettoyer`, hooks `deleting` et `deleted`, hook `creating` inchangé), `docs/deploiement.md`, `docs/schema_bd.md`, `resources/views/filament/widgets/guide-relecteur.blade.php`.
- Étape 5, `deploiement.md` : aucune section nommée « commandes d'exploitation ». Paragraphe ajouté sous « Tâches planifiées », libellé « Commande manuelle, non planifiée ».
- Étape 5, `schema_bd.md` : le grep de la tâche ne remonte que la ligne « Supprimer une scène utilisée… », correcte et inchangée. Deux modifications : (1) § Administration des comptes, la cascade SQL depuis `users` ne passe pas par le hook : la phrase le dit et renvoie à `scenes:purger-orphelines` ; (2) paragraphe « Suppression d'une histoire » ajouté en fin de § Scene et partage entre histoires, avec la règle demandée.
- Étape 4 : assertion `doesntExpectOutputToContain` sur le titre de la scène ajoutée au test de purge ancienne, pour couvrir AC-04. Aucun test ajouté au-delà de la liste.
- Étape 4, tests 1 à 3 : `next_scene_id` passé explicitement à `Choix::factory()`, sinon la factory crée une scène orpheline supplémentaire.
- Vérification : aucune commande lancée hormis `php -l` sur les 4 fichiers PHP (« No syntax errors » sur chacun). Pint, PHPStan, tests et `scenes:purger-orphelines` non lancés (consigne). Balise `</content>` absente des 7 fichiers écrits ou modifiés.
- Décision requise : un choix d'une scène conservée, dont la cible est supprimée, reste avec `next_scene_id = NULL` et `issue = NULL`. Le schéma lit `next_scene_id NULL` comme « sortie du parcours » et `issue NULL` comme « poursuit l'histoire ». L'état est contradictoire. Le test 3 impose ce comportement. Options : (a) accepter et documenter ; (b) refuser la suppression si un choix hors histoire vise la scène ; (c) poser `issue = defavorable` sur ce choix. Recommandation : (b), car le front lit aujourd'hui un choix sans cible comme une fin de parcours. (b) change le test 3.
- Atomicité : suppression de l'histoire et nettoyage des scènes ne sont pas dans une transaction commune. Si le nettoyage échoue après le DELETE, l'histoire est supprimée et ses scènes exclusives restent orphelines jusqu'à `scenes:purger-orphelines` (au-delà de 24 h). Pas de rollback automatique. Englobage possible en surchargeant `delete()` : hors étapes, non fait.
- Limites du hook : non déclenché par `Histoire::query()->delete()` ni par la cascade SQL depuis `users`. `SuppressionCompteService` transfère les histoires avant suppression, donc cette cascade est inerte en pratique. `$scenesANettoyer` garde une entrée si `delete()` lève une exception entre `deleting` et `deleted`.
- Impact sur l'existant, non exécuté : `HistoireResourceTest` (suppression groupée, ligne 200) et `AuditHistoireTest` (lignes 329-367) suppriment des histoires sans scène. Aucun changement de résultat attendu.
- Pré-existant hors périmètre : `docs/tasks/T011-limites-de-saisie.md` contient une balise `</content>`. Non modifié.
- Statut de la tâche inchangé (« à faire »). Aucun commit, aucun push. Parcours navigateur non effectué (consigne).

- Décision de l orchestrateur (2026-10-28) : option (a) retenue. Un choix d une scène conservée dont la cible est supprimée garde next_scene_id et issue à NULL. Ce n est pas contradictoire dans l application : ScenesHistoire l affiche Suite à choisir et VerificationHistoireService le signale Choix à compléter, état déjà utilisé quand une scène suivante n existe pas encore. Pas de transaction commune autour du delete et du nettoyage : en cas d échec, scenes:purger-orphelines rattrape après 24 h.
