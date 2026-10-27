# T005 — Autoriser le relecteur comme repreneur

Statut : à faire
Story : US-01 · Exigences : F-09, T-08
Dépend de : T001
Parallélisable : oui
Exécutant : back

## Objectif

Quand l'admin supprime un compte auteur d'histoires, le repreneur peut être un rédacteur ou un relecteur. Un webmaster, un admin ou un visiteur reste refusé.

## Fichiers à lire

- `app/Services/SuppressionCompteService.php` — `repreneursPossibles()`, `supprimer()`.
- `app/Filament/Resources/UserResource.php` — `configurerSuppression()`, libellés du formulaire de reprise.
- `tests/Feature/Filament/UserResourceTest.php` — tests de reprise (lignes ~213-258).
- `tests/Feature/PurgeComptesInactifsTest.php` — vérifier qu'aucune assertion ne dépend du message d'erreur.
- Rechercher le message : `grep -rn "Un autre rédacteur" app tests`.

## Fichiers cibles

| Fichier | Action | Ce qui change |
|---|---|---|
| `app/Services/SuppressionCompteService.php` | modifier | Rôles de repreneur élargis, message d'erreur. |
| `app/Filament/Resources/UserResource.php` | modifier | Libellés de la fenêtre de reprise. |
| `tests/Feature/Filament/UserResourceTest.php` | modifier | Un test ajouté, un renommé. |

## Étapes

1. Dans `SuppressionCompteService`, ajouter une méthode privée :

```php
    /**
     * Roles pouvant reprendre les histoires d'un compte supprime.
     *
     * @return list<string>
     */
    private function rolesRepreneurs(): array
    {
        return [UserRole::Redacteur->value, UserRole::Relecteur->value];
    }
```

2. `repreneursPossibles()` : remplacer `User::where('role', UserRole::Redacteur->value)` par `User::whereIn('role', $this->rolesRepreneurs())`.
3. `supprimer()` : remplacer `->where('role', UserRole::Redacteur->value)` par `->whereIn('role', $this->rolesRepreneurs())`. Le message de l'exception devient `'Un autre rédacteur ou un relecteur doit reprendre les histoires de ce compte.'`.
4. Docblock de la méthode `repreneursPossibles` : « Redacteurs et relecteurs pouvant reprendre… ».
5. Dans `UserResource::configurerSuppression()` :
   - `->label('Rédacteur qui reprend les histoires')` devient `->label('Compte qui reprend les histoires')` ;
   - texte d'aide sans repreneur : `'Aucun autre rédacteur ni relecteur : attribuez d\'abord l\'un de ces rôles à un compte.'` ;
   - commentaire de méthode : « un autre redacteur ou relecteur doit les reprendre ».
6. Dans `UserResourceTest.php` :
   - renommer `test_le_repreneur_doit_etre_un_redacteur` en `test_le_repreneur_doit_etre_un_redacteur_ou_un_relecteur` (même contenu, un webmaster reste refusé) ;
   - ajouter `test_un_relecteur_peut_reprendre_les_histoires` : copie de `test_les_histoires_d_un_redacteur_supprime_sont_transferees` avec `$repreneur` de rôle `Relecteur`.
7. Si un test dépendait du message ou du libellé modifié, l'adapter.
8. Lancer la vérification.

## Critères d'acceptation

- [ ] AC-01 : Étant donné un rédacteur auteur, quand l'admin le supprime en choisissant un relecteur, alors les histoires appartiennent au relecteur et le compte est supprimé.
- [ ] AC-02 : Étant donné un webmaster forgé comme repreneur, alors rien n'est supprimé.
- [ ] AC-03 : La liste des repreneurs proposés contient rédacteurs et relecteurs, jamais le compte supprimé.

## Vérification

```bash
docker compose exec sc_back vendor/bin/pint --test
docker compose exec sc_back vendor/bin/phpstan analyse
docker compose exec sc_back php artisan test --filter="UserResourceTest|PurgeComptesInactifsTest"
```

Parcours navigateur : admin, supprimer un rédacteur auteur : la liste « Compte qui reprend les histoires » propose les relecteurs.

## Hors périmètre

- La purge automatique des comptes inactifs ne change pas.
- Ne pas modifier la policy `UserPolicy`.

## Notes d'exécution
