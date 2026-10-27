# T003 — États accessibles selon le rôle

Statut : à faire
Story : US-03, US-04, US-05 · Exigences : F-04, F-05, T-05
Dépend de : T002
Parallélisable : non
Exécutant : back

## Objectif

Le champ « État » n'offre que les états que le rôle peut poser. Un rédacteur ne peut aller que de `brouillon` à `relecture`. Un relecteur choisit parmi les quatre. Une valeur forgée est refusée côté serveur. L'onglet « 1. Infos » disparaît pour un rédacteur dont l'histoire est verrouillée.

## Fichiers à lire

- `app/Filament/Resources/HistoireResource.php` — `form()`, champ `etat`.
- `app/Policies/HistoirePolicy.php` — `etatsAccessibles()` (écrit en T002).
- `app/Filament/Resources/HistoireResource/Pages/EditHistoire.php`.
- `app/Filament/Resources/HistoireResource/Pages/ScenesHistoire.php` — `getNavigationItems()` : modèle pour lire `$urlParameters['record']`.
- `vendor/filament/filament/src/Resources/Pages/Page.php` et `vendor/filament/filament/src/Pages/Page.php` — signature de `shouldRegisterNavigation`.
- `tests/Feature/Filament/HistoireResourceTest.php` — conventions.

## Fichiers cibles

| Fichier | Action | Ce qui change |
|---|---|---|
| `app/Filament/Resources/HistoireResource.php` | modifier | Options d'état par rôle, règle `in`, aide contextuelle. |
| `app/Filament/Resources/HistoireResource/Pages/EditHistoire.php` | modifier | Onglet masqué si non modifiable. |
| `tests/Feature/Filament/HistoireResourceTest.php` | modifier | Quatre tests. |

## Étapes

1. Dans `HistoireResource.php`, ajouter la méthode (importer `App\Policies\HistoirePolicy`) :

```php
    /**
     * Etats que l'utilisateur connecte peut poser sur cette histoire.
     *
     * @return array<string, string> valeur => libelle
     */
    public static function optionsEtat(?Histoire $histoire): array
    {
        $utilisateur = auth()->user();

        if ($histoire === null || $utilisateur === null) {
            return [];
        }

        return collect(app(HistoirePolicy::class)->etatsAccessibles($utilisateur, $histoire))
            ->mapWithKeys(fn (EtatHistoire $etat): array => [$etat->value => $etat->libelle()])
            ->all();
    }
```

2. Dans `form()`, champ `Select::make('etat')` :
   - remplacer `->options(array_combine(...))` par `->options(fn (?Histoire $record): array => static::optionsEtat($record))` ;
   - ajouter `->in(fn (?Histoire $record): array => array_keys(static::optionsEtat($record)))` après `->required()` ;
   - remplacer `->helperText('Brouillon → Relecture → Validé → Publié. Seul « Publié » est visible sur le site.')` par une fermeture :

```php
->helperText(fn (): string => auth()->user()?->role === UserRole::Relecteur
    ? 'Brouillon → Relecture → Validé → Publié. Seul « Publié » est visible sur le site. Vous pouvez passer d\'un état à n\'importe quel autre.'
    : 'Passez l\'histoire en « Relecture » quand elle est prête. Elle sera alors verrouillée : un relecteur la corrige, la publie ou vous la renvoie en brouillon.')
```

     Importer `App\Enums\UserRole`.
   - Le filtre `SelectFilter::make('etat')` de la table garde les quatre états (ne pas le modifier).
3. Dans `EditHistoire.php`, ajouter (importer `App\Models\Histoire`) :

```php
    /**
     * L'onglet n'existe pas pour une histoire que l'utilisateur ne peut pas modifier.
     *
     * @param  array<string, mixed>  $parameters
     */
    public static function shouldRegisterNavigation(array $parameters = []): bool
    {
        $histoire = $parameters['record'] ?? null;

        if ($histoire instanceof Histoire) {
            return auth()->user()?->can('update', $histoire) ?? false;
        }

        return parent::shouldRegisterNavigation($parameters);
    }
```

   Vérifier d'abord dans `vendor/` que `shouldRegisterNavigation` accepte `array $parameters = []` et que les onglets de sous-navigation l'appellent avec `record`. Si la signature diffère, adapter et le noter dans « Notes d'exécution ». Si l'onglet ne peut pas être masqué proprement, ne pas insister : laisser l'onglet (la page répond 403) et le noter.
4. Ajouter dans `HistoireResourceTest.php` :
   - `test_un_redacteur_n_a_que_brouillon_et_relecture` : rédacteur, histoire `pour($redacteur)` en `brouillon` ; `HistoireResource::optionsEtat($histoire)` a pour clés `['brouillon', 'relecture']`.
   - `test_un_redacteur_soumet_son_histoire_en_relecture` : `Livewire::test(EditHistoire::class, [...])->fillForm(['etat' => 'relecture'])->call('save')->assertHasNoFormErrors()` ; l'état est `Relecture`.
   - `test_un_redacteur_ne_peut_pas_forcer_la_publication` : `fillForm(['etat' => 'publie'])->call('save')->assertHasFormErrors(['etat'])` ; l'état reste `Brouillon`. Idem avec `valide`.
   - `test_un_relecteur_fait_passer_l_histoire_par_tous_les_etats` : relecteur, histoire d'un autre auteur en `relecture` ; successivement `valide`, `publie`, `brouillon` via le formulaire ; chaque `save` sans erreur ; état final `Brouillon`.
5. Lancer la vérification.

## Critères d'acceptation

- [ ] AC-01 : Étant donné un rédacteur et son histoire en `brouillon`, quand il envoie `etat = publie`, alors le formulaire est refusé et l'état ne change pas.
- [ ] AC-02 : Étant donné le même rédacteur, quand il envoie `relecture`, alors l'enregistrement réussit.
- [ ] AC-03 : Étant donné un relecteur et une histoire `publie`, quand il choisit `brouillon`, alors l'histoire n'est plus servie par l'API publique (`/api/histoires/{ref}` renvoie 404).
- [ ] AC-04 : L'aide du champ « État » change selon le rôle.

## Vérification

```bash
docker compose exec sc_back vendor/bin/pint --test
docker compose exec sc_back vendor/bin/phpstan analyse
docker compose exec sc_back php artisan test --filter="HistoireResourceTest|HistoireScenesTest"
```

Parcours navigateur : connecté en rédacteur, ouvrir une histoire en brouillon, ouvrir la liste déroulante « État » : deux choix seulement. Passer en « Relecture » : l'onglet « 1. Infos » disparaît.

## Hors périmètre

- Page Scènes en lecture seule (T004).
- Journalisation du changement d'état (T008).
- Ne pas retirer ni renommer d'état dans `EtatHistoire`.

## Notes d'exécution

- Vérification non lancée : consigne utilisateur (aucune commande pint, phpstan, artisan test). À lancer en fin de lot, les trois commandes de « Vérification ».
- Point 3 confirmé dans `vendor/` (Filament 3.3.57) : `Resources\Pages\Page::shouldRegisterNavigation(array $parameters = [])`. `InteractsWithRecord::getSubNavigationParameters()` passe `['record' => Histoire]` à `HasSubNavigation::generateNavigationItems()`. Signature identique, aucune adaptation.
- Redondance : `vendor/filament/filament/src/Resources/Pages/EditRecord.php:403` renvoie déjà `parent::shouldRegisterNavigation($parameters) && canEdit($parameters['record'])`, soit `HistoirePolicy::update`. L'onglet « 1. Infos » est donc déjà masqué pour un rédacteur sur histoire verrouillée, avant T003. L'override ajouté duplique ce contrôle. Décision à prendre : le retirer ou le garder comme garde explicite.
- `CreateHistoire` utilise un wizard (`getSteps()`) sans champ `etat` : la règle `in` sur un record null n'est pas atteignable.
- La règle `in` est évaluée sur l'état stocké avant `save()` (validation avant mise à jour du record).
- AC-03 : aucune modification. `HistoireController::show` ligne 14 renvoie 404 si `etat !== Publie`. Aucun test ajouté (hors liste des quatre tests).
- AC-04 : closure `helperText` du champ `etat` dans `form()`. Aucun test ajouté (hors liste).
- Tests écrits, non exécutés.
