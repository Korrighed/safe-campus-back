# T013 — Corriger l'année du pied de page

Statut : à faire
Story : hors lot (correction ajoutée en cours de lot) · Exigences : aucune
Dépend de : T012
Parallélisable : non
Exécutant : executor

## Objectif

Le pied de page du back-office affiche « © 2026 Université de Nouvelle-Calédonie » au lieu de « © 2025 ».

## Fichiers à lire

- `resources/views/filament/footer.blade.php` — ligne 5.

## Fichiers cibles

| Fichier | Action | Ce qui change |
|---|---|---|
| `resources/views/filament/footer.blade.php` | modifier | `2025` devient `2026`. |

## Étapes

1. Dans `resources/views/filament/footer.blade.php`, remplacer `© 2025 Université de Nouvelle-Calédonie` par `© 2026 Université de Nouvelle-Calédonie`. Ne rien changer d'autre dans la ligne.
2. Vérifier qu'aucun autre « 2025 » ne subsiste dans les vues : `grep -rn "2025" resources/ app/ lang/`. Le résultat doit être vide. S'il reste une occurrence, ne pas la modifier : la signaler dans « Notes d'exécution ».

## Critères d'acceptation

- [ ] AC-01 : Étant donné le back-office, quand une page s'affiche, alors le pied de page indique « © 2026 Université de Nouvelle-Calédonie ».
- [ ] AC-02 : `grep -rn "2025" resources/ app/ lang/` ne retourne rien.

## Vérification

```bash
grep -rn "2025" resources/ app/ lang/
```

Parcours navigateur : ouvrir n'importe quelle page du back-office, lire le pied de page.

## Hors périmètre

- Pas d'année dynamique (`date('Y')`) : l'année affichée est une décision de contenu, pas un calcul.
- Les pages légales du front (`Safe-Campus-front`) ne sont pas concernées par cette tâche.
- Les documents de `docs/` (dates d'audit, historiques) ne sont pas modifiés.

## Notes d'exécution
