# T015 — Tracer la suppression groupée d'histoires

Statut : à faire
Story : US-06 · Exigences : NF-03
Dépend de : T012
Parallélisable : oui (avec T014)
Exécutant : back

## Objectif

L'action « Supprimer la sélection » de la liste des histoires écrit un événement `Histoire.suppression_groupee` qui liste les identifiants supprimés et ceux refusés. Les suppressions individuelles restent tracées une par une par `Histoire.supprime` (inchangé).

## Constat de départ

- Chaque `delete()` d'une histoire écrit déjà `Histoire.supprime` (`AuditObserver::deleted`).
- Les histoires refusées par la policy (rédacteur, état ≠ `brouillon`) ne laissent aucune trace.
- Rien ne relie entre elles les suppressions d'une même action groupée.

## Fichiers à lire

- `app/Filament/Resources/HistoireResource.php` — `BulkAction::make('supprimer')` dans `table()`.
- `app/Services/AuditService.php` — `ecrire()` (accepte `$cible = null`).
- `tests/Feature/AuditHistoireTest.php` — helpers `ligne()`, `lignes()`, `json()`.
- `tests/Feature/Filament/HistoireResourceTest.php` — `test_la_suppression_groupee_ignore_les_histoires_verrouillees` (modèle d'appel de l'action).
- `docs/securite-rgpd.md` — § 7, ligne « Événements ».

## Fichiers cibles

| Fichier | Action | Ce qui change |
|---|---|---|
| `app/Filament/Resources/HistoireResource.php` | modifier | Événement dans l'action groupée. |
| `tests/Feature/AuditHistoireTest.php` | modifier | Deux tests. |
| `docs/securite-rgpd.md` | modifier | Événement ajouté à la liste. |
| `docs/TRD.md` | modifier | T-11 : événement ajouté. |

## Étapes

1. Dans `HistoireResource.php`, importer `App\Services\AuditService`. Dans la fermeture de l'action `supprimer`, après `$supprimables->each->delete();` et avant la notification, ajouter :

```php
                            app(AuditService::class)->ecrire('Histoire.suppression_groupee', null, [
                                'supprimees' => $supprimables->modelKeys(),
                                'refusees' => $refusees->modelKeys(),
                            ]);
```

   Identifiants seulement : jamais de titre.
2. `AuditHistoireTest.php`, ajouter :
   - `test_une_suppression_groupee_est_tracee_avec_les_supprimees_et_les_refusees` : rédacteur connecté ; deux histoires à lui, l'une en `Brouillon`, l'autre en `Relecture` ; `Livewire::test(ListHistoires::class)->callTableBulkAction('supprimer', [les deux])` ; la ligne `Histoire.suppression_groupee` a `supprimees === [id du brouillon]`, `refusees === [id de la relecture]`, `auteur.role === 'redacteur'` ; la ligne `Histoire.supprime` existe pour le brouillon seulement.
   - `test_une_suppression_groupee_ne_contient_aucun_titre` : titres distinctifs sur les deux histoires ; la chaîne `json()` de la seule ligne `Histoire.suppression_groupee` ne contient aucun de ces titres.
3. `docs/securite-rgpd.md` § 7, ligne « Événements » : ajouter `Histoire.suppression_groupee` (identifiants supprimés et refusés). `docs/TRD.md` T-11 : même ajout.

## Critères d'acceptation

- [ ] AC-01 : Étant donné une sélection de deux histoires dont une verrouillée, quand le rédacteur supprime la sélection, alors une ligne `Histoire.suppression_groupee` liste l'identifiant supprimé et l'identifiant refusé.
- [ ] AC-02 : L'événement ne contient aucun titre.
- [ ] AC-03 : `Histoire.supprime` est toujours écrit pour chaque histoire réellement supprimée.

## Vérification

Aucune exécution pendant le lot (consigne). À lancer en fin de lot :

```bash
docker compose exec sc_back php artisan test --filter="AuditHistoireTest|HistoireResourceTest"
```

Parcours navigateur : aucun (pas d'impact visible).

## Hors périmètre

- Ne pas tracer le refus de suppression d'une scène partagée.
- Ne pas modifier le comportement de l'action (qui est supprimé, qui est refusé).

## Notes d'exécution

- Branche vérifiée : `feature/role-relecteur`. Aucun commit, aucune écriture git.
- Consigne utilisateur : aucune vérification hors `php -l`. `php artisan test` (AuditHistoireTest, HistoireResourceTest) non exécuté, à lancer en fin de lot.
- `php -l` (via `docker compose exec -T sc_back`) : OK sur `app/Filament/Resources/HistoireResource.php` et `tests/Feature/AuditHistoireTest.php`.
- Étape 1 : `use App\Services\AuditService;` déjà présent (ligne 10), non ajouté.
- Écart à l'étape 1 : `->values()` ajouté avant `->modelKeys()` sur `$supprimables` et `$refusees`. Raison : `partition()` conserve les clés d'origine (`Arr::partition`). Sans `values()`, si l'histoire supprimée n'est pas la première de la sélection, `supprimees` est sérialisé en objet JSON (`{"1": 7}`) et `assertSame([...])` échoue. Le comportement de l'action est inchangé.
- Étape 2 : `ListHistoires` importé dans `AuditHistoireTest.php`. Deux tests ajoutés, en fin de classe.
- Test « ne contient aucun titre » : `json()` concatène toutes les lignes. `Histoire.supprime` porte `valeurs.titre` (`AuditService::CHAMPS_CONTENU` ne contient pas `Histoire`, TRD § 7 : titre d'histoire non masqué). L'assertion porte donc sur `json_encode` de la seule ligne `Histoire.suppression_groupee`, mêmes options que `json()`.
- Étape 3 : `docs/securite-rgpd.md` § 7 (ligne « Événements ») et `docs/TRD.md` T-11 complétés.
- Hors périmètre, non modifié : aucune transaction autour de la boucle `each->delete()`. Chaque suppression est validée seule. Si une suppression lève une exception, les précédentes restent supprimées et `Histoire.suppression_groupee` n'est pas écrit.
- Statut de la tâche laissé à « à faire ».
