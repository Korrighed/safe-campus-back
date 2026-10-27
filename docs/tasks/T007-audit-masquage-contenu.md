# T007 — Masquer le contenu rédigé dans l'audit

Statut : à faire
Story : US-06 · Exigences : NF-02, T-09
Dépend de : aucune
Parallélisable : oui
Exécutant : back

## Objectif

Le canal `audit` n'écrit plus jamais `Scene.titre`, `Scene.dialogue_text` ni `Choix.text_choix`. À la place, une modification porte la liste des noms de champs modifiés. Les autres modèles gardent le comportement actuel.

## Fichiers à lire

- `app/Services/AuditService.php` — `nettoyer()`, `decrire()`, `ecrire()`.
- `app/Observers/AuditObserver.php` — `created`, `updated`, `deleted`.
- `tests/Feature/AuditLogTest.php` — capture des logs en mémoire (`setUp`, `ligne()`).
- `database/factories/SceneFactory.php` et `ChoixFactory.php` — états disponibles (lire seulement).
- Rechercher les autres appels : `grep -rn "nettoyer(" app tests`.

## Fichiers cibles

| Fichier | Action | Ce qui change |
|---|---|---|
| `app/Services/AuditService.php` | modifier | Constante `CHAMPS_CONTENU`, filtre dans `nettoyer()`, `decrire()`. |
| `app/Observers/AuditObserver.php` | modifier | Passe le modèle à `nettoyer()`, ajoute `champs_modifies`. |
| `tests/Feature/AuditLogTest.php` | modifier | Quatre tests. |

## Étapes

1. Dans `AuditService`, importer `App\Models\Choix` et `App\Models\Scene`. Ajouter, sous `CHAMPS_SENSIBLES` :

```php
    /**
     * Contenu redige par les redacteurs : jamais ecrit dans les logs, ni en
     * valeur ni en libelle. Seuls l'identifiant et le nom des champs
     * modifies sont traces.
     *
     * @var array<class-string<Model>, list<string>>
     */
    public const CHAMPS_CONTENU = [
        Scene::class => ['titre', 'dialogue_text'],
        Choix::class => ['text_choix'],
    ];
```

2. Changer la signature : `public function nettoyer(array $attributs, ?Model $model = null): array`. Dans le corps, après le `array_diff_key` existant, retirer aussi les clés de `self::CHAMPS_CONTENU[$model::class] ?? []` quand `$model` n'est pas null. Garder l'abréviation des valeurs.
3. Dans `decrire()`, calculer `$masque = isset(self::CHAMPS_CONTENU[$model::class]);` et mettre la clé `'libelle'` à `null` quand `$masque` est vrai (le `array_filter` existant la retire). Le reste est inchangé.
4. Dans `AuditObserver` :
   - `created` et `deleted` : appeler `$this->audit->nettoyer($model->getAttributes(), $model)`.
   - `updated` : 
     - `$champs = array_keys($this->audit->nettoyer($model->getChanges()));` (sans modèle : sert seulement à connaître les clés tracables) ;
     - si `$champs === []`, retourner (comportement existant) ;
     - `$apres = $this->audit->nettoyer($model->getChanges(), $model);`
     - `$avant = $this->audit->nettoyer(array_intersect_key($model->getPrevious(), $apres), $model);`
     - `$details = ['champs_modifies' => $champs];` puis ajouter `'avant' => $avant, 'apres' => $apres` seulement si `$apres !== []`.
     - écrire avec `class_basename($model).'.modifie'`.
   - Le commentaire existant sur `getPrevious()` reste.
5. Dans `AuditLogTest`, ajouter (imports `Scene`, `Choix` si absents) :
   - `test_le_contenu_d_une_scene_n_est_jamais_journalise` : créer `Scene::factory()->create(['titre' => 'Titre confidentiel', 'dialogue_text' => 'Dialogue confidentiel'])` ; pour chaque record, `json_encode($record->context)` ne contient ni `Titre confidentiel` ni `Dialogue confidentiel` ; la ligne `Scene.cree` n'a pas de `cible.libelle`.
   - `test_modifier_un_dialogue_trace_le_nom_du_champ_sans_le_texte` : créer la scène, vider le handler (`$this->handler->clear()`), `update(['dialogue_text' => 'Nouveau texte confidentiel'])`. La ligne `Scene.modifie` existe, `champs_modifies === ['dialogue_text']`, ni `avant` ni `apres` ne sont présents, aucun texte dans le JSON.
   - `test_le_texte_d_un_choix_n_est_jamais_journalise` : même principe avec `Choix::factory()->create(['text_choix' => 'Choix confidentiel'])`, puis `update` ; aucun texte dans le JSON ; `champs_modifies === ['text_choix']`.
   - `test_un_champ_non_confidentiel_d_une_scene_reste_trace` : créer une scène et un `SousTheme::factory()->create()`, puis modifier `dialogue_text` ET `sous_theme_id` en même temps. `apres` contient `sous_theme_id` mais pas `dialogue_text` ; `champs_modifies` contient les deux noms.
6. Lancer la vérification. Corriger les tests existants d'audit seulement s'ils cassent par la nouvelle clé `champs_modifies` (ils ne devraient pas).

## Critères d'acceptation

- [ ] AC-01 : Étant donné une scène créée, quand on lit le canal `audit`, alors aucun titre ni dialogue n'y figure et `cible` n'a pas de `libelle`.
- [ ] AC-02 : Étant donné un dialogue modifié, alors l'événement `Scene.modifie` contient `champs_modifies` et aucun texte.
- [ ] AC-03 : Étant donné un choix modifié, alors aucun texte n'est écrit.
- [ ] AC-04 : Les tests existants d'`AuditLogTest` restent verts (Theme, User inchangés).

## Vérification

```bash
docker compose exec sc_back vendor/bin/pint --test
docker compose exec sc_back vendor/bin/phpstan analyse
docker compose exec sc_back php artisan test --filter=AuditLogTest
```

Parcours navigateur : aucun (pas d'impact visible).

## Hors périmètre

- Ne pas masquer `Histoire.titre` ni les autres modèles.
- Ne pas toucher aux logs déjà écrits.
- Ne pas créer d'événements pivot ni d'événement d'état (T008, T009).
- Ne pas toucher `User` (nom, prénom, email restent journalisés).

## Notes d'exécution
