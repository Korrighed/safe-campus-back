# T004 — Scènes : lecture seule et partage limité

Statut : à faire
Story : US-02 · Exigences : F-03, F-07, F-08, T-06, T-07
Dépend de : T002
Parallélisable : oui
Exécutant : back

## Objectif

La page « 2. Scènes » d'une histoire verrouillée s'affiche sans bouton d'écriture. La réutilisation de scènes ne propose que les scènes des histoires que l'utilisateur voit. Supprimer une scène utilisée par une autre histoire est refusé avec un message.

## Fichiers à lire

- `app/Filament/Resources/HistoireResource/Pages/ScenesHistoire.php` — `getScenesReutilisables()`, `reutiliser()`, `supprimerSceneAction()`, `autoriserModification()`.
- `resources/views/filament/resources/histoire-resource/pages/scenes-histoire.blade.php` — tous les éléments qui appellent une action.
- `resources/views/filament/resources/histoire-resource/pages/scenes-histoire-edition.blade.php` et `scenes-histoire-consultation.blade.php` — vérifier qu'aucun bouton d'écriture n'y reste hors d'une édition.
- `app/Models/Histoire.php` — `scopeVisiblePar` (écrit en T002).
- `tests/Feature/Filament/HistoireScenesTest.php`.

## Fichiers cibles

| Fichier | Action | Ce qui change |
|---|---|---|
| `app/Filament/Resources/HistoireResource/Pages/ScenesHistoire.php` | modifier | `peutModifier()`, filtre de réutilisation, refus de suppression partagée. |
| `resources/views/filament/resources/histoire-resource/pages/scenes-histoire.blade.php` | modifier | Boutons d'écriture conditionnés, bandeau de lecture seule. |
| `tests/Feature/Filament/HistoireScenesTest.php` | modifier | Cinq tests. |
| `app/Policies/ScenePolicy.php` | modifier | `update` et `delete` : toutes les histoires de la scène doivent être au rédacteur et en brouillon. |
| `tests/Feature/Policies/HistoirePolicyTest.php` | modifier | Un test sur la scène partagée. |

## Étapes

0. `ScenePolicy.php` (constat de T002 : la règle actuelle accepte la scène dès qu'UNE histoire du rédacteur en brouillon la contient, même si elle est aussi dans l'histoire verrouillée d'un autre auteur). Pour un rédacteur, `update` (et donc `delete`) exige que la scène soit rattachée à au moins une histoire ET que toutes ses histoires soient à lui et en brouillon. Remplacer le bloc rédacteur de `update()` par un appel à une méthode privée :

```php
    private function modifiablePar(User $user, Scene $scene): bool
    {
        $histoires = $scene->histoires()->get(['histoires.id', 'histoires.user_id', 'histoires.etat']);

        return $histoires->isNotEmpty()
            && $histoires->every(fn (Histoire $histoire): bool => (int) $histoire->user_id === (int) $user->id
                && $histoire->etat === EtatHistoire::Brouillon);
    }
```

   `update()` devient `return $this->estRelecteur($user) || ($this->estRedacteur($user) && $this->modifiablePar($user, $scene));`. Importer `App\Models\Histoire`. Dans `HistoirePolicyTest`, ajouter `test_un_redacteur_ne_modifie_pas_une_scene_partagee_avec_l_histoire_d_un_autre` : scène rattachée à une histoire de A (brouillon) et à une histoire de B ; `update` pour A est faux ; pour un relecteur, vrai.

1. Dans `ScenesHistoire.php`, ajouter :

```php
    public function peutModifier(): bool
    {
        return auth()->user()?->can('update', $this->histoire()) ?? false;
    }
```

2. `getScenesReutilisables()` : remplacer `->whereHas('histoires')` par `->whereHas('histoires', fn ($query) => $query->visiblePar(auth()->user()))`, et `->with('histoires:id,titre')` par `->with(['histoires' => fn ($query) => $query->visiblePar(auth()->user())])`. Le libellé continue d'utiliser `$scene->histoires->first()->titre`.
3. `reutiliser()` : dans la requête de `$scene`, remplacer `->whereHas('histoires')` par la même fermeture `visiblePar`. Une scène hors périmètre retombe alors dans le cas « Choisis une scène à réutiliser ».
4. `supprimerSceneAction()` :
   - `modalDescription` devient `'La scène et ses choix seront supprimés définitivement.'` ;
   - dans `->action(...)`, après avoir obtenu `$scene` et avant `$scene->delete()`, ajouter :

```php
                if ($scene->histoires()->whereKeyNot($this->histoire()->getKey())->exists()) {
                    Notification::make()
                        ->warning()
                        ->title('Cette scène est utilisée par une autre histoire.')
                        ->body('Retirez-la de cette histoire plutôt que de la supprimer.')
                        ->send();

                    return;
                }
```

5. Dans `scenes-histoire.blade.php` (lire tout le fichier d'abord) :
   - en haut, après les variables existantes, ajouter `@php $peutModifier = $this->peutModifier(); @endphp` ;
   - si `! $peutModifier`, afficher sous l'en-tête un bandeau : `<x-filament::section compact><p class="text-sm text-gray-600 dark:text-gray-200">Lecture seule : cette histoire n'est plus en brouillon. Elle redevient modifiable si un relecteur la renvoie en brouillon.</p></x-filament::section>` ;
   - envelopper dans `@if ($peutModifier) … @endif` : le bloc des boutons « Nouvelle scène » et « Plus » ; le panneau de réutilisation (`@if ($this->reutilisationOuverte)`) ; le lien « Relier ce choix » (`wire:click="modifier(...)"` du bloc `$choixEnAttente`) ; le groupe `@unless ($enEdition)` qui contient « Modifier » et le menu `⋮`.
   - Le bloc `$choixEnAttente` lui-même peut rester affiché, sans le lien.
6. Dans `HistoireScenesTest.php` (propriété `$this->redacteur` créée en T002), ajouter :
   - `test_une_histoire_verrouillee_est_en_lecture_seule` : histoire `pour($this->redacteur)` en `Relecture` avec une scène ; `Livewire::test(ScenesHistoire::class, ['record' => ...])->assertSuccessful()->assertDontSee('Nouvelle scène')->assertDontSee('Modifier')->assertSee('Lecture seule')`.
   - `test_une_action_d_ecriture_est_refusee_sur_une_histoire_verrouillee` : même histoire ; `->call('nouvelleScene')->assertForbidden()`.
   - `test_les_scenes_d_un_autre_auteur_ne_sont_pas_proposees` : histoire de B contenant une scène « Scène de B » ; histoire de A ; `set('reutilisationOuverte', true)->assertDontSee('Scène de B')` ; puis `set('sceneAReutiliser', $sceneDeB->id)->call('reutiliser')` ne l'ajoute pas (`$histoireDeA->scenes` ne la contient pas).
   - `test_un_relecteur_reutilise_la_scene_d_un_autre_auteur` : relecteur connecté (`$this->actingAs(...)` dans le test) ; la scène de B est proposée.
   - `test_une_scene_partagee_ne_peut_pas_etre_supprimee` : une scène rattachée à deux histoires de A ; `callAction('supprimerScene', arguments: ['scene' => $scene->id])` ; `assertModelExists($scene)` ; et le test existant `test_supprimer_une_scene_la_supprime_definitivement` reste vert.
7. Lancer la vérification.

## Critères d'acceptation

- [ ] AC-01 : Étant donné une histoire de A en `relecture`, quand A ouvre « 2. Scènes », alors aucun bouton d'écriture n'apparaît et le bandeau de lecture seule est visible.
- [ ] AC-02 : Étant donné la même histoire, quand A déclenche `nouvelleScene`, alors 403.
- [ ] AC-03 : Étant donné une scène de B, quand A ouvre la réutilisation, alors elle n'est pas listée et ne peut pas être ajoutée.
- [ ] AC-04 : Étant donné une scène rattachée à deux histoires, quand on tente de la supprimer, alors elle reste en base.
- [ ] AC-05 : Les tests existants de `HistoireScenesTest` sont verts.

## Vérification

```bash
docker compose exec sc_back vendor/bin/pint --test
docker compose exec sc_back vendor/bin/phpstan analyse
docker compose exec sc_back php artisan test --filter=HistoireScenesTest
```

Parcours navigateur : rédacteur A, histoire en relecture, page « 2. Scènes » : lecture seule. Histoire en brouillon : tous les boutons présents.

## Hors périmètre

- Journalisation des événements de scène (T009).
- Aucune modification de `PreviewHistoire` (déjà en lecture seule).
- Ne pas changer la mise en page de la page au-delà du bandeau.

## Notes d'exécution

- Étapes 0 à 6 écrites. Étape 7 (vérification) non lancée : consigne utilisateur, vérification au lot.
- Étape 0 : `ScenePolicy::update` appelle `modifiablePar()` (privée). `use App\Models\Histoire` ajouté.
- Étapes 1 à 4 : `peutModifier()`, refus de suppression d'une scène partagée, `with`/`whereHas` de réutilisation (voir décisions ci-dessous). Le bloc « Comment écrire ton histoire » n'est pas modifié.
- Décisions de l'orchestrateur appliquées (aucune vérification exécutée) :
  - Q1 : pas de masquage du bloc « Comment écrire ton histoire ». Le test de lecture seule utilise `assertDontSeeHtml('wire:click="nouvelleScene"')` et `assertDontSeeHtml('wire:click="modifier(')`, avec `assertSee('Lecture seule')`.
  - Q2 (1) : `modifier()` contrôle `can('update', $scene)` après `sceneDeLHistoire()`. `enregistrer()` contrôle `$estNouvelle || can('update', $scene)` dans la transaction, avant toute écriture. `supprimerSceneAction()` refuse d'abord si `! can('delete', $scene)` (notification « Cette scène ne peut pas être supprimée. »), puis applique le contrôle « utilisée par une autre histoire ». `getSections()` expose `modifiable` ; le bouton « Modifier » est désactivé si `! modifiable`, avec le tooltip « Cette scène est aussi utilisée par une histoire verrouillée ou d'un autre auteur ».
  - Q2 (2) : `getScenesReutilisables()` et `reutiliser()` ajoutent `whereDoesntHave('histoires', whereNotIn(visiblePar))` : une scène n'est réutilisable que si toutes ses histoires sont visibles. `with('histoires:id,titre')` d'origine remis ; les `whereHas`/`with` `visiblePar` précédents retirés (redondants).
  - Tests : `test_les_scenes_d_un_autre_auteur_ne_sont_pas_proposees` et `test_un_relecteur_reutilise_la_scene_d_un_autre_auteur` portent sur une scène aussi rattachée à une histoire de A. Ajoutés : `test_modifier_une_scene_partagee_avec_une_histoire_en_relecture_est_refuse` (403) et `test_une_scene_partagee_avec_une_histoire_verrouillee_n_est_pas_modifiable` (clé `modifiable` via `instance()->getSections()`).
- Non couverts par un test : refus de `enregistrer()` sur scène existante non modifiable, refus de suppression par `can('delete')`.
- Réserve : `visible` ne veut pas dire `modifiable`. Une scène visible de A mais verrouillée par une autre histoire de A peut être rattachée à une autre histoire de A ; elle reste non modifiable.
- N+1 : `getSections()` appelle `can('update', $scene)` pour chaque scène, donc une requête par scène (`modifiablePar()`). Acceptable pour une histoire de quelques scènes ; à optimiser si besoin.
- Mineur : si `sceneEnEdition` n'est pas nul quand l'histoire passe hors brouillon (changement dans un autre onglet), le formulaire d'édition reste affiché (`scenes-histoire-edition`, non gardé). L'enregistrement répond 403.
