# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Stack

Laravel 13 · PHP 8.4 · Filament 3 · PostgreSQL 17

> API/admin backend uniquement. L'UI applicative est [safe-campus-front](https://github.com/Korrighed/safe-campus-front) (Nuxt 4 standalone, repo séparé), qui consomme `routes/api.php`.

👉 Voir [README](README.md) pour l'installation, le stack Docker, la mise en production (§ 5) et la CI (§ 6). `docs/` ne contient que les livrables : cahier des charges techniques, sécurité et RGPD, maintenance évolutive.

> Le code s'édite sur l'hôte, les commandes s'exécutent dans le container via `docker compose exec sc_back <cmd>`.

## Commands

```bash
docker compose up -d
docker compose logs -f sc_back
docker compose down
```

L'entrypoint de `SC_Back` exécute `composer install` puis `artisan serve`. `key:generate` et `migrate` sont à lancer manuellement.

Toutes les commandes PHP/Composer/Artisan s'exécutent **dans le container**, préfixées par `docker compose exec sc_back` :

```bash
docker compose exec sc_back php artisan migrate
```

> `SC_Back` sert déjà sur le port 8000. Ne pas relancer `composer dev` ni `php artisan serve` via `exec` — le bind échouera.

## Architecture

API/admin backend : les contrôleurs renvoient du JSON, pas de vues. L'admin est géré par Filament (autonome, ne dépend d'aucun asset de ce repo).

```
app/
├── Console/Commands/   comptes:purger-inactifs, scenes:purger-orphelines
├── Enums/              Backed enums castés sur les modèles
├── Filament/           Resources, Pages (auth), Widgets (guides par rôle), Forms/Components
├── Http/
│   ├── Controllers/    One controller per resource (PascalCase), max 7 CRUD methods
│   ├── Middleware/     SecurityHeaders (en-têtes de sécurité du back)
│   ├── Requests/       Form Requests for all validation (never inline in controllers)
│   └── Resources/      API Resources : forme JSON des réponses de routes/api.php
├── Listeners/          Audit des connexions, date de dernière connexion
├── Livewire/           Composants Livewire hors Filament
├── Models/             Eloquent — always define $fillable; use $casts for enums/arrays
├── Observers/          AuditObserver (journal d'audit), HistoireObserver (changement d'état)
├── Policies/           Authorization per resource
├── Providers/Filament/ AdminPanelProvider — config du panel Filament
└── Services/           Business logic extracted from controllers (audit, géocodage, purges…)

routes/
├── web.php             Health check `/`, pages légales `/mentions-legales` et `/cgu`
├── api.php             Routes JSON du front Nuxt (login visiteur, themes, sous-themes, histoires)
└── console.php         Planification : `comptes:purger-inactifs` quotidienne
```

Le modèle de données est décrit dans le [cahier des charges § 7](docs/cahier-des-charges-techniques.md#modèle-de-données) ; les colonnes complètes sont dans `database/migrations/`.

## Backend Conventions

**Controllers** return JSON:
```php
return HistoireResource::collection($histoires);
return response()->json(['message' => 'created'], 201);
```

**Routes** (`routes/api.php`) bind models by `ref`, never by numeric id:
```php
Route::get('sous-themes/{sousTheme:ref}', [SousThemeController::class, 'show']);
```
Read routes are public by design. The visitor login is a prototype-only gate for events, not authentication, and is removed at the official launch ([docs/securite-rgpd.md § 4.1](docs/securite-rgpd.md)).

**PostgreSQL-specific patterns:**
- Use `DB::select()` with raw CTEs for recursive scene→choice→scene graph traversal
- Cast JSONB columns as `'array'`; query with `whereJsonContains()`
- PostgreSQL n'indexe pas la colonne référençante d'une FK : déclarer `->index()` explicitement

## Git

- Workflow git flow : `feature/*` pour le dev, merge sur `dev` en `--no-ff`. `hotfix/*` créé depuis `main`.
- Toujours demander confirmation à l'utilisateur avant de merger une `feature/*` sur `dev`. Ne jamais merger de sa propre initiative.
- Une fois une `feature/*` mergée dans `dev` et poussée sur `origin/dev`, supprimer la branche locale (`git branch -d`). Ne pas laisser de branches `feature/*` locales après clôture.
- Jamais de `Co-Authored-By` dans les messages de commit.
- `main` est protégée par un ruleset : PR obligatoire, checks `verif` et `approfondi` verts, pas de force-push. `dev` doit contenir `main` avant une nouvelle PR. Détails : [README § 6](README.md#6-intégration-continue).
- Avant de pousser `dev` : `vendor/bin/pint --test`, `vendor/bin/phpstan analyse` et `php artisan test` doivent passer (même contrôle que le job `verif`).
- Format de message de commit obligatoire :

```
type(scope): phrase résumé.
- ajout 1
- ajout 2
```

Exemple :

```
infra(docker): mise a jour de dépendances sur le conteneur.
- ajout de git
- installation des dépendances php laravel
```
