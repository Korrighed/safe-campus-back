# T019 — Correctifs de la revue : choix orphelins, texte de retrait, traçabilité

Statut : fait
Story : transverse (revue du lot) · Exigences : F-08, NF-03
Dépend de : T016
Parallélisable : oui (avec T018)
Exécutant : back

## Objectif

Corriger les défauts de la revue (`reviewer`, 2026-10-28) qui touchent la suppression de scènes, le texte de la modale « Retirer », la traçabilité de la création d'une histoire et quelques nits.

## Constat (revue)

1. **Majeur** — Supprimer une scène X (ou une histoire H1 qui la contient seule) met à `NULL` le `next_scene_id` des choix qui la visent (`nullOnDelete`). Si un tel choix appartient à une scène S **partagée** avec une histoire H2 publiée ou verrouillée, il était jusqu'ici masqué dans H2 par `HistoireParcoursService` (cible hors histoire) ; après la suppression il devient visible comme choix sans suite ni issue. Un brouillon modifie donc le contenu d'une histoire publiée. Le test 3 de `NettoyageScenesTest` fige ce comportement.
2. **Mineur** — la modale « Retirer » dit que la scène « reste disponible pour être réutilisée » : faux si aucune autre histoire ne l'utilise (non réutilisable, puis purgée).
3. **Mineur** — la création d'une histoire (`CreateHistoire`) n'écrit ni `scene_ajoutee` ni `sous_themes_modifies` : le journal ne permet pas de reconstituer la composition initiale.
4. **Nit** — la requête de réutilisation est dupliquée dans `ScenesHistoire` (`getScenesReutilisables` et `reutiliser`).
5. **Nit** — le commentaire de `EditHistoire::afterSave` affirme que `save()` tourne dans une transaction : faux (le panel n'active pas `databaseTransactions()`).
6. **Hors lot constaté** — `ScenesHistoire::enregistrer()` supprime les choix retirés par une requête de masse : aucun `Choix.supprime` n'est écrit.

## Fichiers à lire

- `app/Services/NettoyageScenesService.php`, `tests/Feature/NettoyageScenesTest.php` (test 3).
- `app/Services/HistoireParcoursService.php` lignes ~25-50.
- `app/Filament/Resources/HistoireResource/Pages/ScenesHistoire.php` — `getScenesReutilisables()`, `reutiliser()`, `retirerSceneAction()`, `supprimerSceneAction()`, `enregistrer()` (suppression des choix).
- `app/Filament/Resources/HistoireResource/Pages/CreateHistoire.php`, `EditHistoire.php`.
- `tests/Feature/AuditHistoireTest.php`, `tests/Feature/Filament/HistoireScenesTest.php`.
- `vendor/filament/filament/src/Resources/Pages/CreateRecord.php` — méthode `create()` : ordre de `handleRecordCreation`, `saveRelationships` et `afterCreate`.

## Fichiers cibles

| Fichier | Action | Ce qui change |
|---|---|---|
| `app/Services/NettoyageScenesService.php` | modifier | Suppression des choix visant une scène supprimée, quand leur scène sert une autre histoire. |
| `ScenesHistoire.php` | modifier | Appel du service, texte de retrait, méthode de requête partagée, choix supprimés un à un. |
| `CreateHistoire.php` | modifier | Événements de composition initiale. |
| `EditHistoire.php` | modifier | Commentaire exact. |
| `NettoyageScenesTest.php`, `HistoireScenesTest.php`, `AuditHistoireTest.php` | modifier | Tests. |

## Étapes

1. `NettoyageScenesService` : ajouter la méthode ci-dessous, et l'appeler dans `supprimerOrphelinesParmi()` et `purgerOrphelines()` **avant** `$scene->delete()` :

```php
    /**
     * Supprime les choix qui visent $scene depuis une scene partagee avec une
     * histoire qui ne contient pas $scene. Ils etaient invisibles dans ces
     * histoires (cible hors histoire) : sans cela, la suppression les ferait
     * apparaitre comme des choix sans suite ni issue dans une histoire
     * publiee ou verrouillee. Les choix des scenes qui ne servent que
     * l'histoire d'ou la scene est retiree restent, a completer.
     */
    public function retirerChoixVisantLaScene(Scene $scene): void
    {
        Choix::query()
            ->where('next_scene_id', $scene->getKey())
            ->whereHas('scene.histoires', fn ($histoires) => $histoires->whereDoesntHave(
                'scenes',
                fn ($scenes) => $scenes->whereKey($scene->getKey()),
            ))
            ->get()
            ->each->delete();
    }
```

   (importer `App\Models\Choix`). Chaque suppression passe par le modèle, donc par l'audit (`Choix.supprime`).
2. `ScenesHistoire::supprimerSceneAction()` : après les gardes existantes et **avant** `$scene->delete()`, appeler `app(NettoyageScenesService::class)->retirerChoixVisantLaScene($scene);`.
3. `ScenesHistoire::enregistrer()` : remplacer la suppression de masse des choix retirés (`$scene->choix()->whereNotIn('id', $conserves)->delete()`) par `$scene->choix()->whereNotIn('id', $conserves)->get()->each->delete()`.
4. `ScenesHistoire::retirerSceneAction()` : la description de la modale devient `'La scène est retirée de cette histoire. Si aucune autre histoire ne l\'utilise, elle ne sera plus accessible et sera supprimée au prochain nettoyage.'`
5. `ScenesHistoire` : extraire la requête commune de `getScenesReutilisables()` et `reutiliser()` (whereHas `histoires`, whereDoesntHave histoire courante, whereDoesntHave histoires invisibles) dans une méthode `protected function scenesReutilisablesQuery(): Builder` ; les deux méthodes l'utilisent. Aucun changement de comportement.
6. `CreateHistoire::handleRecordCreation()` : la fermeture de `DB::transaction` retourne l'histoire ; garder la scène créée dans une variable accessible après la transaction (par exemple une propriété privée `?int $premiereSceneId` posée dans la fermeture) puis, après la transaction : `app(AuditService::class)->ecrire('Histoire.scene_ajoutee', $histoire, ['scene_id' => $this->premiereSceneId, 'initiale' => true]);`. Ajouter `afterCreate()` : lire `$this->getRecord()->sousThemes()->pluck('sous_themes.id')` (entiers triés) ; s'il n'est pas vide, `ecrire('Histoire.sous_themes_modifies', $record, ['ajoutes' => $ids, 'retires' => []])`. Avant d'écrire `afterCreate`, lire `CreateRecord::create()` dans `vendor/` : si les relations ne sont pas encore enregistrées à ce moment, utiliser le bon hook et le noter dans « Notes d'exécution ».
7. `EditHistoire` : corriger le commentaire de `afterSave` : `save()` n'ouvre pas de transaction côté panel ; `DB::afterCommit` s'exécute alors immédiatement, et reste correct si `databaseTransactions()` est activé un jour.
8. Tests :
   - `NettoyageScenesTest` : réécrire le test 3 (« choix d'une scène conservée vers une scène supprimée ») : S partagée entre H1 et H2, X exclusive de H1, choix S→X ; supprimer H1 ; X supprimée, S existe, **le choix n'existe plus** (`assertModelMissing`). Ajouter `test_un_choix_d_une_scene_exclusive_de_l_histoire_supprimee_disparait_avec_elle` si pas déjà couvert par la cascade.
   - `HistoireScenesTest` : `test_supprimer_une_scene_retire_les_choix_visants_depuis_une_scene_partagee` : S dans H1 et H2 (H2 publiée), X dans H1 seulement, choix S→X, plus un choix T→X où T n'est que dans H1 ; `callAction('supprimerScene', arguments: ['scene' => X->id])` sur H1 ; le choix S→X n'existe plus, le choix T→X existe avec `next_scene_id === null`.
   - `AuditHistoireTest` : `test_creer_une_histoire_trace_la_premiere_scene_et_les_sous_themes` : via `Livewire::test(CreateHistoire::class)` avec un sous-thème ; lignes `Histoire.scene_ajoutee` (avec `initiale === true`) et `Histoire.sous_themes_modifies` (`ajoutes` = l'id) ; `json()` ne contient ni le titre de la scène ni son dialogue.
   - `AuditHistoireTest` : `test_supprimer_un_choix_en_editant_une_scene_est_trace` : une scène avec deux choix, `enregistrer` avec un seul ; une ligne `Choix.supprime` existe.

## Critères d'acceptation

- [ ] AC-01 : Étant donné une scène S partagée avec une histoire H2, quand la scène X que vise un choix de S est supprimée (par la suppression de H1 ou depuis la page Scènes), alors ce choix est supprimé et H2 ne voit aucun nouveau choix sans suite.
- [ ] AC-02 : La modale « Retirer » décrit exactement ce qui se passe.
- [ ] AC-03 : La création d'une histoire écrit `scene_ajoutee` (initiale) et `sous_themes_modifies`, sans aucun texte.
- [ ] AC-04 : Les choix retirés d'une scène éditée écrivent `Choix.supprime`.
- [ ] AC-05 : Les tests existants de scènes et de nettoyage restent valides, hormis le test 3 réécrit.

## Vérification

Aucune exécution pendant le lot (consigne). Contrôle autorisé : `php -l` sur les fichiers PHP modifiés. À lancer en fin de lot :

```bash
docker compose exec sc_back vendor/bin/pint --test
docker compose exec sc_back vendor/bin/phpstan analyse --memory-limit=512M
docker compose exec sc_back php artisan test --filter="NettoyageScenesTest|HistoireScenesTest|AuditHistoireTest"
```

Parcours navigateur : aucun (pas d'impact visible hors le texte de la modale).

## Hors périmètre

- Ne pas toucher `PreviewHistoire.php`, `HistoireResource.php`, les policies (T018).
- Ne pas modifier `HistoireParcoursService` ni l'API.
- Pas d'optimisation du N+1 de `getSections()` (noté, hors lot).

## Notes d'exécution

- Branche vérifiée : `feature/role-relecteur`. Aucun commit, aucune écriture git.
- Consigne utilisateur : aucune vérification hors `php -l`. `pint`, `phpstan` et `php artisan test` non exécutés. Les tests ajoutés ou réécrits ne sont donc pas exécutés.
- `php -l` (via `docker compose exec -T sc_back`) : OK sur les 7 fichiers PHP modifiés. Relecture de fin de fichier : aucune balise parasite.
- Étape 1 : `retirerChoixVisantLaScene()` ajoutée à `NettoyageScenesService`, appelée avant `delete()` dans `supprimerOrphelinesParmi()` et `purgerOrphelines()`. `$scenes->each->delete()` remplacé par une closure pour l'appel. `use App\Models\Choix` ajouté.
- Étape 2 : appel dans `supprimerSceneAction()` après les gardes, avant `delete()`.
- Étape 3 : `->get()->each->delete()` dans `enregistrer()`. Chaque choix retiré passe par le modèle et écrit `Choix.supprime`.
- Étape 4 : texte appliqué tel quel. Voir « Décision requise » ci-dessous.
- Étape 5 : `scenesReutilisablesQuery(): Builder` ajoutée. `getScenesReutilisables()` et `reutiliser()` l'utilisent. `use Illuminate\Database\Eloquent\Builder` ajouté.
- Étape 6 : `vendor/filament/filament/src/Resources/Pages/CreateRecord.php`, `create()` : `handleRecordCreation()` (l.107), puis `saveRelationships()` (l.109), puis `afterCreate` via `callHook` (l.111). Les sous-thèmes (`Select::relationship('sousThemes')`) sont donc en base quand `afterCreate()` les lit. Hook retenu : `afterCreate()`.
- Étape 6, trace `Histoire.scene_ajoutee` : écrite après la `DB::transaction` interne de `handleRecordCreation()`. `databaseTransactions()` n'est pas activé (défaut `false`, aucune occurrence dans `app/`), donc pas de transaction englobante. Si un jour activé, la trace précéderait le commit externe : passer alors à `DB::afterCommit`.
- Étape 7 : commentaire corrigé. Vérifié : `DB::afterCommit` exécute le callback immédiatement hors transaction (`DatabaseTransactionsManager::addCallback`).
- Étape 8, `NettoyageScenesTest` : test 3 renommé `test_le_choix_d_une_scene_conservee_vers_une_scene_supprimee_disparait`, assertion `assertModelMissing($choix)`. Test « choix d'une scène exclusive » non ajouté : déjà couvert par `test_supprimer_une_histoire_supprime_ses_scenes_orphelines` (choix `$intro` vers `$salon`, asserté absent).
- Étape 8, `HistoireScenesTest` : `test_supprimer_une_scene_retire_les_choix_visants_depuis_une_scene_partagee` ajouté. Assertion ajoutée : `$intro->choix()->count() === 0`, pour couvrir AC-01 (H2 ne gagne aucun choix).
- Étape 8, `AuditHistoireTest` : `test_creer_une_histoire_trace_la_premiere_scene_et_les_sous_themes` et `test_supprimer_un_choix_en_editant_une_scene_est_trace` ajoutés. Imports `CreateHistoire` et `Choix` ajoutés.
- Dépendance : `HistoireScenesTest` suppose que `ScenePolicy::delete()` autorise la suppression de X (toutes les histoires de X en brouillon, au propriétaire). T018 modifie `ScenePolicy` : à re-vérifier au lancement des tests.
- Décision requise (AC-02) : le texte de l'étape 4 dit « sera supprimée au prochain nettoyage ». Le code ne supprime qu'une scène orpheline créée il y a plus de 24 h (`DELAI_MINIMUM_HEURES`, sur `created_at`). Pour une scène créée depuis moins de 24 h, le texte est faux. La commande `scenes:purger-orphelines` n'est pas planifiée (`routes/console.php` ne planifie que `comptes:purger-inactifs`). Proposition : « La scène est retirée de cette histoire. Si aucune autre histoire ne l'utilise, elle ne sera plus accessible. Si elle a été créée il y a plus de 24 heures, elle sera supprimée au prochain nettoyage manuel. »
- Risque (hors étape, non modifié) : la modale « Supprimer » dit « La scène et ses choix seront supprimés définitivement ». Elle ne mentionne pas que les choix d'autres scènes visant cette scène sont aussi retirés.
- Risque (hors étape, non modifié) : `retirerChoixVisantLaScene()` et `delete()` ne sont pas dans une transaction commune. Si `delete()` échoue, les choix visant la scène sont déjà supprimés et la scène reste. Proposition : envelopper les deux dans `DB::transaction`.
- Non couvert par test : le chemin `purgerOrphelines()` (commande `scenes:purger-orphelines`) n'a pas de test de suppression des choix entrants.
- Statut de la tâche laissé à « à faire ».
