# T009 — Événements de rattachement

Statut : à faire
Story : US-06 · Exigences : NF-03, T-11
Dépend de : T003, T004, T008
Parallélisable : non
Exécutant : back

## Objectif

Les changements qui passent par les tables pivot sont tracés : scène ajoutée, retirée, déplacée, départ défini, sous-thèmes modifiés. Chaque événement écrit des identifiants, jamais un texte.

## Fichiers à lire

- `app/Filament/Resources/HistoireResource/Pages/ScenesHistoire.php` — `enregistrer()`, `reutiliser()`, `retirerSceneAction()`, `definirDepart()`, `deplacer()`, `rattacher()`.
- `app/Filament/Resources/HistoireResource/Pages/EditHistoire.php` — `afterSave()`.
- `vendor/filament/filament/src/Resources/Pages/EditRecord.php` (méthode `save()`) et `vendor/filament/forms/src/Form.php` / `vendor/filament/forms/src/ComponentContainer.php` — trouver à quel moment `saveRelationships` s'exécute par rapport à `beforeSave` et `afterSave`.
- `app/Services/AuditService.php` — `ecrire()`.
- `tests/Feature/AuditHistoireTest.php` (créé en T008).

## Fichiers cibles

| Fichier | Action | Ce qui change |
|---|---|---|
| `app/Filament/Resources/HistoireResource/Pages/ScenesHistoire.php` | modifier | Méthode `tracer()` et cinq appels. |
| `app/Filament/Resources/HistoireResource/Pages/EditHistoire.php` | modifier | Écart de sous-thèmes. |
| `tests/Feature/AuditHistoireTest.php` | modifier | Six tests. |

## Étapes

1. Dans `ScenesHistoire.php`, importer `App\Services\AuditService` et ajouter :

```php
    /**
     * Evenement d'audit sur l'histoire ouverte. Identifiants uniquement :
     * jamais le titre ni le texte d'une scene.
     *
     * @param  array<string, mixed>  $details
     */
    protected function tracer(string $action, array $details): void
    {
        app(AuditService::class)->ecrire($action, $this->histoire(), $details);
    }
```

2. Appels à ajouter, toujours APRÈS la réussite de l'opération et HORS d'une fermeture `DB::transaction` (un événement écrit dans une transaction annulée serait faux) :
   - `enregistrer()` : après le `DB::transaction(...)`, si `$estNouvelle`, `$this->tracer('Histoire.scene_ajoutee', ['scene_id' => $scene->getKey()]);`
   - `reutiliser()` : après `$this->rattacher($scene);`, `$this->tracer('Histoire.scene_ajoutee', ['scene_id' => $scene->getKey(), 'reutilisee' => true]);`
   - `retirerSceneAction()` : après `detach`, `$this->tracer('Histoire.scene_retiree', ['scene_id' => $scene->getKey()]);`
   - `definirDepart()` : après la transaction, `$this->tracer('Histoire.depart_defini', ['scene_id' => $scene->getKey()]);`
   - `deplacer()` : après la transaction finale (pas dans le `return` anticipé), `$this->tracer('Histoire.scene_deplacee', ['scene_id' => $sceneId, 'sens' => $sens < 0 ? 'haut' : 'bas']);`
3. Dans `EditHistoire.php` : tracer l'écart des sous-thèmes.
   - Ajouter une propriété `private array $sousThemesAvant = [];`.
   - Dans `beforeSave()` (à créer) : `$this->sousThemesAvant = $this->histoire()->sousThemes()->pluck('sous_themes.id')->map(fn ($id) => (int) $id)->sort()->values()->all();`
   - Dans `afterSave()`, au tout début (avant le `return` pour l'état brouillon, car le contrôle de complétude ne doit pas masquer la trace) : calculer `$apres` de la même façon, puis `$ajoutes = array_values(array_diff($apres, $this->sousThemesAvant)); $retires = array_values(array_diff($this->sousThemesAvant, $apres));` ; si l'un des deux n'est pas vide, `app(AuditService::class)->ecrire('Histoire.sous_themes_modifies', $this->histoire(), ['ajoutes' => $ajoutes, 'retires' => $retires]);`
   - Si, d'après la lecture de `vendor/`, les relations ne sont pas encore sauvegardées quand `afterSave()` s'exécute, déplacer la comparaison dans le hook qui suit leur sauvegarde et le noter dans « Notes d'exécution ». Le test du point 4 est la preuve : il doit passer.
4. Dans `AuditHistoireTest.php`, ajouter (rédacteur connecté, histoire `pour($redacteur)`) :
   - `test_ajouter_une_scene_est_trace` : `Livewire::test(ScenesHistoire::class, ...)->call('nouvelleScene')->set('donnees.titre', 'Titre secret')->set('donnees.dialogue_text', 'Texte secret')->call('enregistrer')` (reprendre la forme d'un test existant de `HistoireScenesTest` pour remplir le formulaire). Une ligne `Histoire.scene_ajoutee` avec `scene_id` ; `json()` ne contient ni `Titre secret` ni `Texte secret`.
   - `test_reutiliser_une_scene_est_trace` : `reutilisee === true`.
   - `test_retirer_une_scene_est_trace`.
   - `test_definir_le_depart_est_trace`.
   - `test_deplacer_une_scene_est_trace` : `sens === 'bas'` après `descendre`.
   - `test_modifier_les_sous_themes_est_trace` : `Livewire::test(EditHistoire::class, ...)->fillForm(['sousThemes' => [$a->id]])->call('save')` puis `fillForm(['sousThemes' => [$b->id]])->call('save')` ; deuxième ligne : `ajoutes === [$b->id]`, `retires === [$a->id]`.
5. Lancer la vérification.

## Critères d'acceptation

- [ ] AC-01 : Étant donné un rédacteur, quand il retire une scène de son histoire, alors une ligne `Histoire.scene_retiree` porte l'identifiant de la scène.
- [ ] AC-02 : Étant donné des sous-thèmes modifiés, alors `Histoire.sous_themes_modifies` liste les identifiants ajoutés et retirés.
- [ ] AC-03 : Aucun titre ni texte de scène n'apparaît dans le journal pendant ces tests.
- [ ] AC-04 : Les tests de `HistoireScenesTest` restent verts.

## Vérification

```bash
docker compose exec sc_back vendor/bin/pint --test
docker compose exec sc_back vendor/bin/phpstan analyse
docker compose exec sc_back php artisan test --filter="AuditHistoireTest|HistoireScenesTest|HistoireResourceTest"
```

Parcours navigateur : aucun (pas d'impact visible).

## Hors périmètre

- Rattachements des autres modèles (contacts, médias) : écart S-16, hors lot.
- Accès refusés (T010).

## Notes d'exécution

Ordre des hooks, lu dans `vendor/` (aucune vérification lancée, consigne) :

1. `beginDatabaseTransaction()` (`vendor/filament/filament/src/Resources/Pages/EditRecord.php:144`).
2. `beforeValidate`.
3. `form->getState(afterValidate: ...)` (`vendor/filament/forms/src/Concerns/HasState.php:237`) : validation, dehydratation, puis le callback qui appelle `afterValidate` puis `beforeSave` (`EditRecord.php:148-152`).
4. `saveRelationships()` s'exécute dans `getState()`, après ce callback (`HasState.php:258`). Pour `sousThemes` (Select multiple `relationship()`, `vendor/filament/forms/src/Components/Select.php:993`) : sync du pivot.
5. `mutateFormDataBeforeSave`, puis `handleRecordUpdate()` = `$record->update($data)` (`EditRecord.php:156`). `sousThemes` n'y figure pas (multiple, non dehydraté).
6. `afterSave` (`EditRecord.php:158`), puis `commitDatabaseTransaction()` (`EditRecord.php:171`).

Conclusion : `beforeSave` lit l'état avant le sync, `afterSave` lit l'état après le sync. La comparaison n'a pas à être déplacée. Preuve attendue : `test_modifier_les_sous_themes_est_trace` (non exécuté).

Limites :

- `afterSave` s'exécute dans la transaction de `save()`, avant le commit. Si le commit échoue, la ligne `Histoire.sous_themes_modifies` existe sans changement persisté. C'est contraire à la règle de `AuditObserver` (écriture après commit). Non corrigé, hors spec. Décision requise : accepter, ou écrire via `DB::afterCommit()` (comportement sous `DatabaseTransactions` des tests : inconnu, à vérifier).
- `saveFormComponentOnly()` (`EditRecord.php:184`) appelle `getState()` (donc `saveRelationships()`) avant `beforeSave` (`EditRecord.php:193-203`). La capture « avant » serait déjà synchronisée sur ce chemin. Aucun appelant dans `app/` ni `vendor/` à ce jour. Si une action de section l'utilise sur un bloc contenant `sousThemes`, la trace sera manquée.

Écarts de forme (fond inchangé) :

- Test d'ajout : `fillForm(['titre' => ..., 'dialogue_text' => ...])` au lieu de `set('donnees.titre', ...)`, forme utilisée par les tests existants de `HistoireScenesTest`.
- Helper `histoireAvecScenes()` ajouté dans `AuditHistoireTest.php` : `histoireATroisScenes()` de `HistoireScenesTest` est `private`.

Pré-existant, hors périmètre, non corrigé :

- `ScenesHistoire.php`, `getSections()` : `'badges' =>$this->badges(...)`, espace manquant avant `$this`. Présent dans HEAD. Pint le signalera probablement au lot (à confirmer).
- `git status` avant modification : `docs/tasks/T012-documentation.md` modifié et non commité. Non touché par cette tâche.

Vérification : non lancée (consigne utilisateur). À lancer une fois au lot : `pint --test`, `phpstan analyse`, `php artisan test --filter="AuditHistoireTest|HistoireScenesTest|HistoireResourceTest"`. AC-04 dépend de cette exécution.
