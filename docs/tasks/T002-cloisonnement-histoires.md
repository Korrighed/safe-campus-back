# T002 — Cloisonner les histoires par auteur et par état

Statut : à faire
Story : US-02 · Exigences : F-02, F-03, F-05, F-06, T-03, T-04
Dépend de : T001
Parallélisable : non
Exécutant : back

## Objectif

Un rédacteur ne voit, n'ouvre et ne prévisualise que ses histoires (404 sinon). Il ne modifie ni ne supprime une histoire que si elle est en `brouillon`. Un relecteur voit et modifie tout, supprime tout, ne crée rien. Les tests existants sont adaptés à cette règle.

## Fichiers à lire

- `app/Policies/HistoirePolicy.php`, `ScenePolicy.php`, `ChoixPolicy.php` — à réécrire.
- `app/Models/Histoire.php`, `Scene.php`, `Choix.php` — relations (`Scene::histoires()`, `Choix::scene()`).
- `app/Filament/Resources/HistoireResource.php` — requête, actions de table, `histoireOuverte()`.
- `app/Filament/Resources/HistoireResource/Pages/EditHistoire.php` — `DeleteAction`.
- `database/factories/HistoireFactory.php`.
- `tests/Feature/Filament/HistoireResourceTest.php`, `HistoireScenesTest.php`, `HistoirePreviewTest.php` — tests à adapter.

## Fichiers cibles

| Fichier | Action | Ce qui change |
|---|---|---|
| `app/Models/Histoire.php` | modifier | `scopeVisiblePar`. |
| `app/Policies/HistoirePolicy.php` | modifier | Réécriture complète (code ci-dessous). |
| `app/Policies/ScenePolicy.php`, `ChoixPolicy.php` | modifier | Alignées sur l'histoire. |
| `app/Filament/Resources/HistoireResource.php` | modifier | `getEloquentQuery`, actions autorisées, suppression groupée filtrée, `histoireOuverte`. |
| `app/Filament/Resources/HistoireResource/Pages/EditHistoire.php` | modifier | `DeleteAction` autorisée par `delete`. |
| `database/factories/HistoireFactory.php` | modifier | État `pour(User $auteur)`. |
| `tests/Feature/Policies/HistoirePolicyTest.php` | créer | Tests de la matrice. |
| `tests/Feature/Filament/HistoireResourceTest.php`, `HistoireScenesTest.php`, `HistoirePreviewTest.php` | modifier | Auteur explicite. |

## Étapes

1. `Histoire.php` : importer `App\Enums\UserRole` et `App\Models\User` (même namespace pour `User`, ne pas importer). Ajouter après `scopePubliee` :

```php
    /**
     * Histoires que l'utilisateur peut voir : toutes pour un relecteur, les
     * siennes pour un redacteur, aucune pour les autres roles.
     *
     * @param  Builder<Histoire>  $query
     */
    public function scopeVisiblePar(Builder $query, ?User $user): void
    {
        if ($user?->role === UserRole::Relecteur) {
            return;
        }

        if ($user?->role === UserRole::Redacteur) {
            $query->where('histoires.user_id', $user->id);

            return;
        }

        $query->whereRaw('1 = 0');
    }
```

2. `HistoirePolicy.php` : remplacer tout le fichier par :

```php
<?php

namespace App\Policies;

use App\Enums\EtatHistoire;
use App\Enums\UserRole;
use App\Models\Histoire;
use App\Models\User;

/**
 * Histoires : le redacteur ne touche qu'a ses histoires en brouillon, le
 * relecteur a toutes — voir docs/TRD.md § 7.
 */
class HistoirePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->estRedacteur($user) || $this->estRelecteur($user);
    }

    public function view(User $user, Histoire $histoire): bool
    {
        return $this->estRelecteur($user)
            || ($this->estRedacteur($user) && $this->estAuteur($user, $histoire));
    }

    /**
     * Le relecteur ne cree pas d'histoires (pour le moment).
     */
    public function create(User $user): bool
    {
        return $this->estRedacteur($user);
    }

    public function update(User $user, Histoire $histoire): bool
    {
        return $this->estRelecteur($user)
            || ($this->estRedacteur($user)
                && $this->estAuteur($user, $histoire)
                && $histoire->etat === EtatHistoire::Brouillon);
    }

    public function delete(User $user, Histoire $histoire): bool
    {
        return $this->update($user, $histoire);
    }

    /**
     * Autorise l'action groupee ; chaque histoire est ensuite controlee par delete().
     */
    public function deleteAny(User $user): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Etats que l'utilisateur peut poser sur cette histoire.
     *
     * @return list<EtatHistoire>
     */
    public function etatsAccessibles(User $user, Histoire $histoire): array
    {
        if ($this->estRelecteur($user)) {
            return EtatHistoire::cases();
        }

        if ($this->update($user, $histoire)) {
            return [EtatHistoire::Brouillon, EtatHistoire::Relecture];
        }

        return [];
    }

    private function estRedacteur(User $user): bool
    {
        return $user->role === UserRole::Redacteur;
    }

    private function estRelecteur(User $user): bool
    {
        return $user->role === UserRole::Relecteur;
    }

    private function estAuteur(User $user, Histoire $histoire): bool
    {
        return (int) $histoire->user_id === (int) $user->id;
    }
}
```

3. `ScenePolicy.php` : remplacer par la même structure, sur `Scene`. Docblock : « Scenes : alignees sur les histoires qui les contiennent. » Règles :
   - `viewAny` : rédacteur ou relecteur.
   - `view` : relecteur, ou rédacteur dont au moins une histoire contient la scène : `$scene->histoires()->where('histoires.user_id', $user->id)->exists()`.
   - `create` : rédacteur seulement.
   - `update` et `delete` : relecteur, ou rédacteur avec la même requête plus `->where('histoires.etat', EtatHistoire::Brouillon->value)`.
4. `ChoixPolicy.php` : mêmes règles, en déléguant à la scène : `view`, `update`, `delete` → `(new ScenePolicy)->view/update/delete($user, $choix->scene)`. `create` : rédacteur seulement. `viewAny` : rédacteur ou relecteur.
5. `HistoireResource.php` :
   - Importer `App\Models\Histoire` (déjà là) et `Illuminate\Database\Eloquent\Builder` (déjà là). Ajouter, avant `form()` :

```php
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->visiblePar(auth()->user());
    }
```

   - Dans `table()`, actions : `Tables\Actions\Action::make('scenes')` et `make('previsualiser')` reçoivent `->authorize('view')`. `Tables\Actions\EditAction::make()` reçoit `->authorize('update')`.
   - Remplacer `Tables\Actions\DeleteBulkAction::make()` par :

```php
                    Tables\Actions\BulkAction::make('supprimer')
                        ->label('Supprimer la sélection')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records): void {
                            $utilisateur = auth()->user();
                            [$supprimables, $refusees] = $records->partition(fn (Histoire $histoire): bool => $utilisateur?->can('delete', $histoire) ?? false);

                            $supprimables->each->delete();

                            Notification::make()
                                ->title($supprimables->count().' histoire(s) supprimée(s)')
                                ->body($refusees->isNotEmpty() ? $refusees->count().' histoire(s) ne peuvent pas être supprimées dans leur état actuel.' : null)
                                ->color($refusees->isNotEmpty() ? 'warning' : 'success')
                                ->send();
                        }),
```

     Importer `Filament\Notifications\Notification` et `Illuminate\Support\Collection`. (Raison : `DeleteBulkAction` de cette version de Filament ne contrôle pas chaque enregistrement.)
   - Dans `histoireOuverte()`, remplacer `Histoire::find($record)` par `static::getEloquentQuery()->find($record)`.
6. `EditHistoire.php` : `Actions\DeleteAction::make()` reçoit `->authorize('delete')`.
7. `HistoireFactory.php` : ajouter après `publiee()` :

```php
    /**
     * Histoire dont l'auteur est donne.
     */
    public function pour(User $auteur): static
    {
        return $this->state(fn (array $attributes) => ['user_id' => $auteur->id]);
    }
```

8. Adapter les tests existants (ils créent des histoires d'un auteur étranger) :
   - `HistoireScenesTest.php` : dans la classe, ajouter une propriété `private User $redacteur;`. Dans `setUp()`, `$this->redacteur = User::factory()->create(['role' => UserRole::Redacteur]); $this->actingAs($this->redacteur);`. Remplacer chaque `Histoire::factory()->create(` par `Histoire::factory()->pour($this->redacteur)->create(` (lignes autour de 36, 121, 296, 324, 338, 360, 372, 385, 397). Au test `test_une_scene_d_une_autre_histoire_est_reutilisee_en_fin_de_liste` (ligne ~118), `$autre` doit aussi appartenir à `$this->redacteur`.
   - `HistoirePreviewTest.php` : dans chaque test, remplacer `$this->actingAs(User::factory()->create(['role' => UserRole::Redacteur]));` par `$redacteur = User::factory()->create(['role' => UserRole::Redacteur]); $this->actingAs($redacteur);` et `Histoire::factory()->create(` par `Histoire::factory()->pour($redacteur)->create(`.
   - `HistoireResourceTest.php` : `test_le_ref_reste_inchange_a_l_edition` → l'histoire est créée avec `->pour($redacteur)`. `test_l_auteur_est_affiche_dans_la_liste_et_dans_les_infos` → l'utilisateur connecté devient un relecteur (`UserRole::Relecteur`) pour voir l'histoire d'un autre auteur.
9. Créer `tests/Feature/Policies/HistoirePolicyTest.php` (classe `Tests\Feature\Policies\HistoirePolicyTest extends TestCase`), un test par ligne :
   - rédacteur voit et modifie sa histoire `brouillon` ; ne modifie pas la même en `relecture` ; la voit encore (`view`) ;
   - rédacteur ne voit pas l'histoire d'un autre (`view`, `update`, `delete` faux) ;
   - relecteur : `view`, `update`, `delete` vrais sur une histoire d'autrui en `publie` ; `create` faux ;
   - webmaster, admin, sans rôle : `viewAny` faux ;
   - `etatsAccessibles` : relecteur → 4 états ; rédacteur sur `brouillon` → `[Brouillon, Relecture]` ; rédacteur sur `relecture` → `[]`.
   Utiliser `$this->assertTrue(Gate::forUser($user)->allows('update', $histoire))` ou `$user->can(...)`.
10. Ajouter dans `HistoireResourceTest.php` :
   - `test_un_redacteur_ne_voit_que_ses_histoires` : `ListHistoires` → `assertCanSeeTableRecords([sienne])`, `assertCanNotSeeTableRecords([autre])`.
   - `test_un_redacteur_ne_peut_pas_ouvrir_l_histoire_d_un_autre` : `$this->expectException(ModelNotFoundException::class);` puis `Livewire::test(EditHistoire::class, ['record' => $autre->getKey()]);` (même convention que dans `HistoireScenesTest`).
   - `test_un_redacteur_ne_peut_pas_modifier_une_histoire_en_relecture` : histoire à lui, état `Relecture`, `Livewire::test(EditHistoire::class, [...])->assertForbidden()`.
   - `test_un_relecteur_voit_toutes_les_histoires` : `assertCanSeeTableRecords` sur deux histoires d'auteurs différents.
   - `test_un_relecteur_ne_peut_pas_creer` : `Livewire::test(CreateHistoire::class)->assertForbidden()` et `Livewire::test(ListHistoires::class)->assertActionHidden('create')`.
   - `test_la_suppression_groupee_ignore_les_histoires_verrouillees` : rédacteur avec une histoire `brouillon` et une `relecture` ; `callTableBulkAction('supprimer', [les deux])` ; la première disparaît, la seconde reste.
11. Lancer la vérification.

## Critères d'acceptation

- [ ] AC-01 : Étant donné deux rédacteurs, quand A liste les histoires, alors celles de B n'apparaissent pas.
- [ ] AC-02 : Étant donné une histoire de B, quand A ouvre son URL, alors 404 (`ModelNotFoundException`).
- [ ] AC-03 : Étant donné une histoire de A en `relecture`, quand A ouvre sa page Infos, alors 403.
- [ ] AC-04 : Étant donné un relecteur, quand il ouvre la liste, alors il voit toutes les histoires et l'action de création est absente.
- [ ] AC-05 : La suppression groupée ne supprime que les histoires que `delete` autorise.
- [ ] AC-06 : Tous les tests existants de `HistoireResourceTest`, `HistoireScenesTest`, `HistoirePreviewTest` et `HistoireApiTest` sont verts.

## Vérification

```bash
docker compose exec sc_back vendor/bin/pint --test
docker compose exec sc_back vendor/bin/phpstan analyse
docker compose exec sc_back php artisan test --filter="Histoire"
```

Parcours navigateur : passe `qa` en fin de lot (rédacteur A et B, relecteur).

## Hors périmètre

- Options d'état du formulaire (T003).
- Lecture seule de la page Scènes et réutilisation de scènes (T004).
- Journalisation (T008 à T010).
- Ne pas modifier `MediaPolicy` ni les autres policies.

## Notes d'exécution
