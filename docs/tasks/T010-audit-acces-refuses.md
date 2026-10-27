# T010 — Journaliser les accès refusés

Statut : à faire
Story : US-06 · Exigences : NF-03, T-12
Dépend de : T009
Parallélisable : non
Exécutant : back

## Objectif

Une tentative d'accès à une histoire hors périmètre ou non autorisée écrit un événement `Histoire.acces_refuse`. La réponse reste 404 (hors périmètre) ou 403 (droit refusé).

## Fichiers à lire

- `app/Filament/Resources/HistoireResource.php` — `getEloquentQuery()` (T002).
- `vendor/filament/filament/src/Resources/Resource.php` — `resolveRecordRouteBinding()` (ligne ~188).
- `vendor/filament/filament/src/Resources/Pages/EditRecord.php` — `authorizeAccess()` (ligne ~82) : signature exacte.
- `app/Filament/Resources/HistoireResource/Pages/ScenesHistoire.php` — `mount()`, `autoriserModification()`.
- `app/Filament/Resources/HistoireResource/Pages/PreviewHistoire.php` — `mount()`.
- `app/Filament/Resources/HistoireResource/Pages/Concerns/LitHistoire.php` — trait voisin.
- `tests/Feature/AuditHistoireTest.php`.

## Fichiers cibles

| Fichier | Action | Ce qui change |
|---|---|---|
| `app/Filament/Resources/HistoireResource/Pages/Concerns/JournaliseLesRefus.php` | créer | Trait d'écriture de l'événement. |
| `app/Filament/Resources/HistoireResource.php` | modifier | `resolveRecordRouteBinding()`. |
| `app/Filament/Resources/HistoireResource/Pages/EditHistoire.php` | modifier | `authorizeAccess()`. |
| `app/Filament/Resources/HistoireResource/Pages/ScenesHistoire.php` | modifier | `mount()`, `autoriserModification()`. |
| `app/Filament/Resources/HistoireResource/Pages/PreviewHistoire.php` | modifier | `mount()`. |
| `tests/Feature/AuditHistoireTest.php` | modifier | Quatre tests. |

## Étapes

1. Créer le trait `JournaliseLesRefus` (namespace `App\Filament\Resources\HistoireResource\Pages\Concerns`) :

```php
<?php

namespace App\Filament\Resources\HistoireResource\Pages\Concerns;

use App\Services\AuditService;

/**
 * Trace un refus d'acces sur l'histoire ouverte. A utiliser avec LitHistoire.
 */
trait JournaliseLesRefus
{
    protected function journaliserRefus(string $capacite): void
    {
        app(AuditService::class)->ecrire('Histoire.acces_refuse', $this->histoire(), [
            'motif' => 'droit_refuse',
            'capacite' => $capacite,
        ]);
    }
}
```

2. `HistoireResource.php` : importer `App\Services\AuditService` et `Illuminate\Database\Eloquent\Model`. Ajouter :

```php
    /**
     * Une histoire hors du perimetre de l'utilisateur reste un 404 (son
     * existence n'est pas revelee), mais la tentative est tracee.
     */
    public static function resolveRecordRouteBinding(int|string $key): ?Model
    {
        $histoire = parent::resolveRecordRouteBinding($key);

        if ($histoire === null && auth()->check() && $existante = Histoire::query()->whereKey($key)->first()) {
            app(AuditService::class)->ecrire('Histoire.acces_refuse', $existante, ['motif' => 'hors_perimetre']);
        }

        return $histoire;
    }
```

3. `EditHistoire.php` : utiliser le trait (`use JournaliseLesRefus;`) et surcharger, après avoir vérifié la signature dans `vendor/` :

```php
    protected function authorizeAccess(): void
    {
        try {
            parent::authorizeAccess();
        } catch (HttpException $exception) {
            $this->journaliserRefus('update');

            throw $exception;
        }
    }
```

   Importer `Symfony\Component\HttpKernel\Exception\HttpException`.
4. `ScenesHistoire.php` : utiliser le trait. Dans `mount()`, remplacer la ligne `abort_unless(auth()->user()?->can('view', $this->histoire()), 403);` par :

```php
        if (! auth()->user()?->can('view', $this->histoire())) {
            $this->journaliserRefus('view');
            abort(403);
        }
```

   Dans `autoriserModification()`, même transformation avec `'update'`.
5. `PreviewHistoire.php` : utiliser le trait ; même transformation dans `mount()` avec `'view'`.
6. Dans `AuditHistoireTest.php`, ajouter :
   - `test_ouvrir_l_histoire_d_un_autre_auteur_est_trace` : rédacteur A connecté, histoire de B. `try { Livewire::test(EditHistoire::class, ['record' => $histoireDeB->getKey()]); } catch (ModelNotFoundException) {}`. Une ligne `Histoire.acces_refuse` avec `motif === 'hors_perimetre'` et `cible.id === $histoireDeB->id`.
   - `test_une_page_infos_verrouillee_est_tracee` : A, histoire à lui en `Relecture` ; `Livewire::test(EditHistoire::class, [...])->assertForbidden()` ; ligne avec `motif === 'droit_refuse'` et `capacite === 'update'`.
   - `test_une_ecriture_de_scene_refusee_est_tracee` : même histoire ; `Livewire::test(ScenesHistoire::class, [...])->call('nouvelleScene')->assertForbidden()` ; ligne avec `capacite === 'update'`.
   - `test_un_acces_autorise_ne_produit_pas_d_evenement` : A ouvre sa propre histoire en `Brouillon` ; aucune ligne `Histoire.acces_refuse`.
7. Lancer la vérification.

## Critères d'acceptation

- [ ] AC-01 : Étant donné deux rédacteurs, quand A ouvre l'URL d'une histoire de B, alors la réponse est 404 et `Histoire.acces_refuse` (`hors_perimetre`) est écrit.
- [ ] AC-02 : Étant donné une histoire verrouillée, quand A ouvre « Infos », alors 403 et événement `droit_refuse`.
- [ ] AC-03 : Un accès autorisé n'écrit rien.
- [ ] AC-04 : Tous les tests des histoires restent verts.

## Vérification

```bash
docker compose exec sc_back vendor/bin/pint --test
docker compose exec sc_back vendor/bin/phpstan analyse
docker compose exec sc_back php artisan test --filter="Histoire|AuditLogTest"
```

Parcours navigateur : aucun (pas d'impact visible).

## Hors périmètre

- Journaliser les refus sur les autres ressources.
- Ne pas journaliser les contrôles d'affichage (boutons masqués) : seulement les pages et les actions refusées.

## Notes d'exécution
