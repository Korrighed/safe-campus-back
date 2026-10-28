# T018 — Correctifs de la revue : aperçu, policies, PHPStan, Pint, tests

Statut : à faire
Story : transverse (revue du lot) · Exigences : F-02, NF-01
Dépend de : T016
Parallélisable : oui (avec T019)
Exécutant : back

## Objectif

Corriger les défauts de la revue (`reviewer`, 2026-10-28) qui touchent l'aperçu, les policies, la modale « Vérifier », PHPStan, Pint et la fragilité de deux tests. Le défaut critique est une fuite : un rédacteur peut lire le texte d'une scène d'un autre auteur par l'aperçu.

## Constat (revue)

1. **Critique** — `PreviewHistoire.php` : `sceneActuelleId` et `choixFinalId` sont des propriétés Livewire publiques modifiables par le client. `Scene::find()` et la lecture du choix ne vérifient pas l'appartenance à l'histoire. Un rédacteur fait `$wire.set('sceneActuelleId', <id d'une scène d'un autre>)` et lit son texte. Second chemin : `choisir()` suit un `next_scene_id` qui sort de l'histoire.
2. **Mineur** — la modale « Vérifier » propose « Corriger » vers la page Infos pour une histoire verrouillée : le clic donne 403 et écrit un faux `Histoire.acces_refuse`.
3. **PHPStan** — `HistoireResource::getEloquentQuery()` (`visiblePar` introuvable sur `Builder<Model>`), l'action groupée (`Support\Collection` sans `modelKeys()`), `histoireOuverte()` (`find()` renvoie `Model|null`).
4. **Pint** — `new ScenePolicy()` et `new HistoirePolicy()` avec parenthèses.
5. **Policies** — `ScenePolicy::create` et `ChoixPolicy::create` refusent le relecteur, alors que la page Scènes lui permet d'ajouter scènes et choix.
6. **Tests fragiles** — `HistoireResourceTest` (assertions de table sur la page 1 d'une base seedée) ; `UserRoleTest` (e-mail fixe).

## Fichiers à lire

- `app/Filament/Resources/HistoireResource/Pages/PreviewHistoire.php` — en entier.
- `resources/views/filament/resources/histoire-resource/pages/preview-histoire.blade.php`.
- `app/Services/HistoireParcoursService.php` lignes ~30-45 — modèle du refus d'une cible hors histoire.
- `resources/views/filament/resources/histoire-resource/verification.blade.php` (ligne ~29) et `HistoireResource::urlCorrection()`.
- `app/Filament/Resources/HistoireResource.php` — `getEloquentQuery()`, action groupée, `histoireOuverte()`.
- `app/Policies/ScenePolicy.php`, `ChoixPolicy.php`, `tests/Feature/Policies/HistoirePolicyTest.php`.
- `tests/Feature/Filament/HistoireResourceTest.php`, `tests/Feature/Filament/HistoirePreviewTest.php`, `tests/Feature/Database/UserRoleTest.php`.

## Fichiers cibles

| Fichier | Action | Ce qui change |
|---|---|---|
| `PreviewHistoire.php` | modifier | Propriétés verrouillées, scène et choix bornés à l'histoire, cible hors histoire refusée. |
| `HistoirePreviewTest.php` | modifier | Deux tests. |
| `verification.blade.php` | modifier | Lien « Corriger » seulement si l'histoire est modifiable. |
| `HistoireResource.php` | modifier | Types PHPStan. |
| `ScenePolicy.php`, `ChoixPolicy.php` | modifier | `create` ouvert au relecteur ; `new` sans parenthèses. |
| `HistoirePolicyTest.php` | modifier | `new` sans parenthèses ; assertion de `create`. |
| `HistoireResourceTest.php`, `UserRoleTest.php` | modifier | Tests moins fragiles. |

## Étapes

1. **Aperçu (critique).** Lire `PreviewHistoire.php` et sa vue. Puis :
   - importer `Livewire\Attributes\Locked` et marquer `#[Locked]` les propriétés publiques `sceneActuelleId` et `choixFinalId` (et toute autre propriété publique d'état de l'aperçu que le client n'a aucune raison de poser) ;
   - `getSceneActuelle()` : charger la scène par `$this->histoire()->scenes()->whereKey($this->sceneActuelleId)->with(['choix', 'media'])->first()` au lieu de `Scene::find()` ;
   - `getChoixFinal()` : ne retourner un choix que s'il appartient à une scène de l'histoire, par exemple `Choix::query()->whereKey($this->choixFinalId)->whereIn('scene_id', $this->histoire()->scenes()->select('scenes.id'))->first()` ;
   - `choisir()` : lire le code existant (il refuse déjà un choix d'une autre scène que la scène actuelle). Ajouter : si `next_scene_id` n'est pas nul et n'appartient pas aux scènes de l'histoire, ne pas avancer (même règle que `HistoireParcoursService`, lignes ~37-40). Ne rien changer d'autre à son comportement.
2. **Tests de l'aperçu** (`HistoirePreviewTest.php`) :
   - `test_les_proprietes_de_l_apercu_sont_verrouillees` : rédacteur connecté, aperçu de sa propre histoire ; `$this->expectException(\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class)` (vérifier le nom exact de la classe dans `vendor/livewire/` avant de l'écrire) puis `Livewire::test(PreviewHistoire::class, [...])->set('sceneActuelleId', $sceneDUnAutre->id)` ;
   - `test_un_choix_qui_mene_hors_de_l_histoire_n_avance_pas` : scène S de l'histoire avec un choix dont `next_scene_id` est une scène d'une autre histoire ; `->call('choisir', $choix->id)` ; `sceneActuelleId` reste l'id de S.
3. **Modale « Vérifier »** : dans `verification.blade.php`, n'afficher le lien « Corriger » que si `auth()->user()?->can('update', $histoire)`. Sinon, afficher le point sans lien. Lire d'abord la vue pour garder la mise en forme.
4. **PHPStan** dans `HistoireResource.php` :
   - `getEloquentQuery()` : annoter le résultat du parent : `/** @var Builder<Histoire> $requete */ $requete = parent::getEloquentQuery(); return $requete->visiblePar(auth()->user());`
   - action groupée : typer le paramètre de la fermeture `Illuminate\Database\Eloquent\Collection $records` (alias d'import `EloquentCollection` si `Illuminate\Support\Collection` reste utilisé ailleurs ; sinon remplacer l'import) ;
   - `histoireOuverte()` : `$histoire = static::getEloquentQuery()->find($record); return $histoire instanceof Histoire ? $histoire : null;` (après le cas `$record instanceof Histoire`).
5. **Pint** : dans `ChoixPolicy.php` (lignes ~21, 31, 36) et `HistoirePolicyTest.php` (ligne ~63), écrire `new ScenePolicy` et `new HistoirePolicy` sans parenthèses. Chercher d'autres `new Classe()` sans argument dans les fichiers modifiés du lot : `git diff --name-only dev...HEAD -- '*.php'` puis `grep -nE "new [A-Z][A-Za-z]+\(\)" <fichiers>`. Corriger chaque occurrence (hors `new class`).
6. **Policies** : `ScenePolicy::create` et `ChoixPolicy::create` retournent vrai pour un rédacteur ou un relecteur (docblock : « le relecteur ajoute des scènes et des choix en corrigeant une histoire »). `HistoirePolicy::create` reste réservé au rédacteur. Adapter `HistoirePolicyTest` si une assertion attendait le refus du relecteur pour une scène ou un choix ; ajouter `test_un_relecteur_peut_creer_une_scene_mais_pas_une_histoire`.
7. **Tests fragiles** :
   - `HistoireResourceTest.php` : pour chaque test qui utilise `assertCanSeeTableRecords` / `assertCanNotSeeTableRecords` sur la liste complète (lignes ~140 et ~181, et tout autre : `grep -n "TableRecords" tests/Feature/Filament/HistoireResourceTest.php`), ajouter `->set('tableRecordsPerPage', 'all')` avant l'assertion ;
   - `UserRoleTest.php` : `test_le_role_relecteur_est_accepte_en_base` utilise une adresse unique : `'relecteur.'.uniqid().'@test.nc'` et l'assertion `assertDatabaseHas` avec la même variable.

## Critères d'acceptation

- [ ] AC-01 : Étant donné un rédacteur dans l'aperçu de son histoire, quand le client tente de poser `sceneActuelleId`, alors Livewire refuse (propriété verrouillée).
- [ ] AC-02 : Étant donné un choix dont la cible est hors de l'histoire, quand on le choisit, alors l'aperçu n'avance pas.
- [ ] AC-03 : Étant donné une histoire verrouillée, la modale « Vérifier » n'offre pas de lien « Corriger » vers la page Infos.
- [ ] AC-04 : `new ScenePolicy` / `new HistoirePolicy` sans parenthèses ; types PHPStan corrigés comme décrit.
- [ ] AC-05 : Un relecteur peut créer scène et choix (policies), pas une histoire.
- [ ] AC-06 : Les tests de liste ne dépendent plus de la première page.

## Vérification

Aucune exécution pendant le lot (consigne). Contrôle autorisé : `php -l` sur les fichiers PHP modifiés. À lancer en fin de lot :

```bash
docker compose exec sc_back vendor/bin/pint --test
docker compose exec sc_back vendor/bin/phpstan analyse --memory-limit=512M
docker compose exec sc_back php artisan test --filter="HistoirePreviewTest|HistoirePolicyTest|HistoireResourceTest|UserRoleTest"
```

Parcours navigateur : rédacteur A, aperçu de son histoire, console du navigateur : `$wire.set('sceneActuelleId', <id d'une scène de B>)` doit être refusé.

## Hors périmètre

- Ne pas toucher `ScenesHistoire.php`, `NettoyageScenesService.php`, `CreateHistoire.php`, `EditHistoire.php`, `AuditHistoireTest.php` (T019).
- Ne pas modifier l'API publique ni `HistoireParcoursService`.

## Notes d'exécution

- Contrôle : `php -l` sans erreur sur les 8 fichiers PHP modifiés. Aucun Pint, PHPStan ni `php artisan test` lancé (consigne utilisateur). Commandes de fin de lot à lancer par l'utilisateur : celles de la section Vérification.
- Étape 1 : `#[Locked]` sur `sceneActuelleId` et `choixFinalId` ; `getSceneActuelle()` et `getChoixFinal()` bornés aux scènes de l'histoire (`whereKey` sur la relation `scenes()`, `whereIn` sur `select('scenes.id')`) ; `choisir()` ne change rien si `next_scene_id` n'appartient pas à l'histoire.
- Étape 2 : `test_les_proprietes_de_l_apercu_sont_verrouillees` attend `Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException` (nom vérifié dans `vendor/livewire`). `test_un_choix_qui_mene_hors_de_l_histoire_n_avance_pas` vérifie `sceneActuelleId` après `call('choisir')`.
- Étape 3 : lien « Corriger » de `verification.blade.php` conditionné par `auth()->user()?->can('update', $histoire)`. Conséquence voulue par la tâche : aucun lien « Corriger » pour une histoire verrouillée, y compris ceux menant à la page Scènes (lisible en lecture seule).
- Étape 4 : `getEloquentQuery()` annoté `Builder<Histoire>` ; import `Illuminate\Support\Collection` remplacé par `Illuminate\Database\Eloquent\Collection` (seul usage du fichier) ; `histoireOuverte()` renvoie `null` si `find()` ne donne pas un `Histoire`.
- Étape 5 : `new ScenePolicy()` ×3 dans `ChoixPolicy.php`, `new HistoirePolicy()` dans `HistoirePolicyTest.php`. Recherche `new Classe()` sur tous les fichiers PHP du lot (`git diff dev...HEAD`) : aucune autre occurrence, hors `new class`.
- Étape 6 : `ScenePolicy::create` et `ChoixPolicy::create` acceptent le relecteur ; `HistoirePolicy` inchangé. Ajout de `test_un_relecteur_peut_creer_une_scene_mais_pas_une_histoire` dans `HistoirePolicyTest.php`. Aucune assertion existante ne refusait le relecteur sur scène ou choix.
- Étape 7 : `->set('tableRecordsPerPage', 'all')` avant les lignes 152-153 et 181 de `HistoireResourceTest.php` (propriété publique de `CanPaginateRecords`, valeur `'all'` vérifiée dans `vendor/filament/tables`). `UserRoleTest.php` : adresse `'relecteur.'.uniqid().'@test.nc'` dans une variable réutilisée par `assertDatabaseHas`.
- Point non traité, à trancher : `test_l_auteur_est_affiche_dans_la_liste_et_dans_les_infos` (`HistoireResourceTest.php`, ligne 140) utilise `assertSee` sur la liste sans `tableRecordsPerPage = 'all'`. Hors grep de l'étape 7, donc non modifié. AC-06 n'est couvert que pour les assertions de table.
- Point non traité, à trancher : `$record` (trait Filament `InteractsWithRecord`, `public Model|int|string|null $record`) reste modifiable par le client. Le verrouiller exige de redéclarer la propriété dans `PreviewHistoire`, hors étapes. Effet réel d'un `set('record', ...)` non vérifié.
