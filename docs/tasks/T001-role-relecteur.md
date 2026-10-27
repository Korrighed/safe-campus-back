# T001 — Ajouter le rôle relecteur

Statut : à faire
Story : US-01 · Exigences : F-01, T-01, T-02
Dépend de : aucune
Parallélisable : non
Exécutant : back

## Objectif

Le rôle `relecteur` existe en base et dans l'enum, l'admin peut l'attribuer, et un relecteur entre dans le panel. Aucun droit sur les histoires n'est donné ici.

## Fichiers à lire

- `app/Enums/UserRole.php` — enum à étendre.
- `database/migrations/2026_09_28_110000_add_admin_to_users_role_check.php` — modèle exact de la migration à écrire.
- `app/Models/User.php` — `canAccessPanel()`.
- `app/Filament/Resources/UserResource.php` — `rolesAttribuables()`.
- `tests/Feature/Database/UserRoleTest.php` et `tests/Feature/Filament/UserResourceTest.php` — conventions de test.

## Fichiers cibles

| Fichier | Action | Ce qui change |
|---|---|---|
| `app/Enums/UserRole.php` | modifier | Nouveau cas et libellé. |
| `database/migrations/2026_10_28_100000_add_relecteur_to_users_role_check.php` | créer | Contrainte SQL à 5 valeurs. |
| `app/Models/User.php` | modifier | `canAccessPanel()` accepte `Relecteur`. |
| `app/Filament/Resources/UserResource.php` | modifier | `rolesAttribuables()` propose `Relecteur`. |
| `tests/Feature/Database/UserRoleTest.php` | modifier | Deux tests. |
| `tests/Feature/Filament/UserResourceTest.php` | modifier | Un test. |

## Étapes

1. Dans `UserRole.php`, ajouter `case Relecteur = 'relecteur';` après `Redacteur`. Dans `libelle()`, ajouter `self::Relecteur => 'Relecteur',` après `self::Redacteur`.
2. Créer la migration `2026_10_28_100000_add_relecteur_to_users_role_check.php`, même structure que celle d'`admin` :
   - `up()` : `DROP CONSTRAINT users_role_check`, puis `ADD CONSTRAINT users_role_check CHECK (role::text = ANY (ARRAY['webmaster'::character varying, 'redacteur'::character varying, 'relecteur'::character varying, 'visiteur'::character varying, 'admin'::character varying]::text[]))`.
   - `down()` : d'abord `DB::table('users')->where('role', 'relecteur')->update(['role' => null]);` (commentaire : les relecteurs repassent en attente, la contrainte d'origine refuserait la valeur), puis `DROP` et `ADD` de la contrainte à 4 valeurs : `webmaster`, `redacteur`, `visiteur`, `admin`.
   - Docblock de la classe : une phrase en français, sans accent comme dans le fichier modèle.
3. Dans `User::canAccessPanel()`, ajouter `UserRole::Relecteur` à la liste (ordre : Webmaster, Redacteur, Relecteur, Admin). Mettre à jour le commentaire de la méthode si elle énumère les rôles.
4. Dans `UserResource::rolesAttribuables()`, la liste devient `[UserRole::Webmaster, UserRole::Redacteur, UserRole::Relecteur, UserRole::Admin]`.
5. Dans `UserRoleTest.php` :
   - dans `test_un_compte_avec_role_accede_au_panel`, ajouter une assertion pour `UserRole::Relecteur` ;
   - ajouter `test_le_role_relecteur_est_accepte_en_base` : insérer une ligne `users` avec `role => 'relecteur'` (mêmes colonnes que `test_le_role_rejette_une_valeur_hors_liste`, adresse `relecteur@test.nc`) et vérifier avec `assertDatabaseHas('users', ['email' => 'relecteur@test.nc', 'role' => 'relecteur'])`.
6. Dans `UserResourceTest.php`, ajouter `test_l_admin_attribue_le_role_relecteur` en copiant `test_l_admin_attribue_un_role_a_une_demande` avec `UserRole::Relecteur`.
7. Appliquer la migration à la base de dev AVANT de lancer les tests (les tests tournent sur cette base) : `docker compose exec sc_back php artisan migrate`.
8. Lancer la vérification.

## Critères d'acceptation

- [ ] AC-01 : Étant donné la migration appliquée, quand on insère `role = 'relecteur'`, alors la ligne est acceptée. `'administrateur'` reste refusé.
- [ ] AC-02 : Étant donné un utilisateur `relecteur`, quand on appelle `canAccessPanel()`, alors le résultat est `true`.
- [ ] AC-03 : Étant donné un admin, quand il attribue « Relecteur » à une demande, alors le rôle est enregistré.
- [ ] AC-04 : `migrate:rollback --step=1` puis `migrate` s'exécutent sans erreur.

## Vérification

```bash
docker compose exec sc_back php artisan migrate
docker compose exec sc_back vendor/bin/pint --test
docker compose exec sc_back vendor/bin/phpstan analyse
docker compose exec sc_back php artisan test --filter="UserRoleTest|UserResourceTest"
docker compose exec sc_back php artisan migrate:rollback --step=1
docker compose exec sc_back php artisan migrate
```

Parcours navigateur : aucun (pas d'impact visible hors liste des rôles de l'admin, couverte par `qa` en fin de lot).

## Hors périmètre

- Aucun droit sur les histoires (T002).
- Ne pas toucher `SuppressionCompteService` (T005).
- Ne pas modifier de documentation (T012).

## Notes d'exécution

- Migration `2026_10_28_100000` appliquée sur la base de dev (batch 3), rollback d'une étape puis re-migration OK. Contrainte vérifiée : 5 valeurs dont `relecteur`.
- PHPStan : premier lancement en crash « PHP memory limit 128M » (limite du conteneur). Relancé avec `--memory-limit=512M` (option CLI, config inchangée) : No errors.
- `docker compose exec -T` utilisé (shell sans TTY).
- `SuppressionCompteService::purgerInactifs()` (T005) purge aussi les relecteurs inactifs depuis 3 ans, comme les autres rôles non admin. Non modifié.
