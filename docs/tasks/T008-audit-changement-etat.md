# T008 — Événement de changement d'état

Statut : à faire
Story : US-06 · Exigences : NF-03, T-10
Dépend de : T007
Parallélisable : non
Exécutant : back

## Objectif

Chaque changement d'état d'une histoire écrit un événement `Histoire.etat_change` avec l'état d'origine et l'état d'arrivée, l'auteur et son rôle. Il s'ajoute à `Histoire.modifie`.

## Fichiers à lire

- `app/Observers/AuditObserver.php` — modèle (après commit, `getPrevious()`).
- `app/Providers/AppServiceProvider.php` — `registerAudit()`.
- `app/Services/AuditService.php` — `ecrire()`.
- `tests/Feature/AuditLogTest.php` — `setUp()` (capture en mémoire) et `ligne()`, à copier.

## Fichiers cibles

| Fichier | Action | Ce qui change |
|---|---|---|
| `app/Observers/HistoireObserver.php` | créer | Événement d'état. |
| `app/Providers/AppServiceProvider.php` | modifier | Enregistrement de l'observer. |
| `tests/Feature/AuditHistoireTest.php` | créer | Classe de tests d'audit des histoires (réutilisée par T009 et T010). |

## Étapes

1. Créer `app/Observers/HistoireObserver.php` :

```php
<?php

namespace App\Observers;

use App\Models\Histoire;
use App\Services\AuditService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

/**
 * Evenement dedie au circuit editorial : retrouver qui a publie, valide ou
 * renvoye une histoire sans parcourir tous les Histoire.modifie. Apres commit,
 * comme AuditObserver.
 */
class HistoireObserver implements ShouldHandleEventsAfterCommit
{
    public function __construct(private readonly AuditService $audit) {}

    public function updated(Histoire $histoire): void
    {
        if (! $histoire->wasChanged('etat')) {
            return;
        }

        $this->audit->ecrire('Histoire.etat_change', $histoire, [
            'de' => $histoire->getPrevious()['etat'] ?? null,
            'vers' => $histoire->etat->value,
        ]);
    }
}
```

2. Dans `AppServiceProvider::registerAudit()`, après la boucle `foreach`, ajouter `Histoire::observe(HistoireObserver::class);` et importer `App\Observers\HistoireObserver`. Compléter le docblock : « L'état d'une histoire a en plus son propre événement (HistoireObserver). »
3. Créer `tests/Feature/AuditHistoireTest.php`, namespace `Tests\Feature`, classe `AuditHistoireTest extends TestCase`. Copier de `AuditLogTest` le champ `$handler`, le `setUp()` (capture du canal `audit` en mémoire) et la méthode `ligne()`. Ajouter deux méthodes d'aide : `lignes(string $action): array` qui renvoie tous les enregistrements dont `message` vaut `$action`, et `json(): string` qui renvoie `json_encode` de tous les contextes concaténés.
4. Tests à écrire dans cette classe :
   - `test_un_changement_d_etat_est_trace_avec_de_et_vers` : relecteur connecté ; `Histoire::factory()->create()` (brouillon) ; `$this->handler->clear()` ; `update(['etat' => EtatHistoire::Relecture])`. La ligne `Histoire.etat_change` a `de === 'brouillon'`, `vers === 'relecture'`, `auteur.role === 'relecteur'`, `cible.type === 'Histoire'`.
   - `test_modifier_le_titre_ne_produit_pas_d_evenement_d_etat` : après `clear()`, `update(['titre' => 'Autre titre'])` ; `ligne('Histoire.etat_change')` est null.
   - `test_le_changement_d_etat_garde_aussi_son_histoire_modifie` : `Histoire.modifie` existe encore, avec `apres.etat === 'relecture'`.
5. Lancer la vérification.

## Critères d'acceptation

- [ ] AC-01 : Étant donné un relecteur, quand il publie une histoire en relecture, alors une ligne `Histoire.etat_change` contient `de: relecture`, `vers: publie`, son email et son rôle.
- [ ] AC-02 : Un changement de titre seul ne produit pas cet événement.
- [ ] AC-03 : Les tests d'`AuditLogTest` restent verts.

## Vérification

```bash
docker compose exec sc_back vendor/bin/pint --test
docker compose exec sc_back vendor/bin/phpstan analyse
docker compose exec sc_back php artisan test --filter="AuditLogTest|AuditHistoireTest"
```

Parcours navigateur : aucun (pas d'impact visible).

## Hors périmètre

- Événements de scènes et de sous-thèmes (T009).
- Accès refusés (T010).
- Ne pas modifier `AuditObserver`.

## Notes d'exécution

- Vérification non lancée : consigne utilisateur (aucun pint, phpstan, artisan test). À lancer en fin de lot, les trois commandes de « Vérification ».
- Relecture manuelle du diff effectuée : syntaxe, imports triés, signatures. Aucun outil exécuté.
- Fichiers : `app/Observers/HistoireObserver.php` créé ; `app/Providers/AppServiceProvider.php` (import, `Histoire::observe(HistoireObserver::class)` après la boucle, docblock) ; `tests/Feature/AuditHistoireTest.php` créé.
- Test ajouté hors étapes : `test_une_publication_depuis_la_relecture_est_tracee_avec_son_auteur` (relecture vers publie, email et rôle). Les étapes ne prévoient que brouillon vers relecture, or AC-01 parle de publication.
- `json()` n'est appelé par aucun test de T008 : prévu pour T009 et T010. Il n'échappe ni les accents ni les slashes, pour que les recherches de texte restent fiables. `JSON_THROW_ON_ERROR` évite un `false` silencieux.
- `Histoire.etat` est NOT NULL (migration `create_histoires_table`, défaut `brouillon`) : `->etat->value` sans garde est sûr.
- Non modifiés : `AuditObserver`, `AuditService`, `AuditLogTest`.
- `docs/tasks/T012-documentation.md` apparaît modifié dans `git status` avant mon intervention. Non touché.
