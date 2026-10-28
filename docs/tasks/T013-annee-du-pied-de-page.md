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

- Exécutant : executor, 2026-10-28.
- Modifié : `resources/views/filament/footer.blade.php` ligne 5, `© 2025 Université de Nouvelle-Calédonie` remplacé par `© 2026 Université de Nouvelle-Calédonie`. Rien d'autre dans la ligne.
- Rendu : `app/Providers/Filament/AdminPanelProvider.php` lignes 83-84 rend `view('filament.footer')` sur `PanelsRenderHook::FOOTER`. Le fichier modifié est bien celui affiché sur les pages du back-office.
- AC-02 : `grep -rn "2025" resources/ app/ lang/` retourne vide. Contrôle croisé : `find` + `grep` sur les 122 fichiers de ces dossiers, 0 occurrence.
- AC-01 : vérifié statiquement (ligne 5 lit « © 2026 Université de Nouvelle-Calédonie »). Parcours navigateur non effectué (pas de serveur lancé, consigne de l'exécutant). À faire par l'utilisateur.
- Aucune autre occurrence de « 2025 » trouvée : rien à signaler.
- Technique : le Bash tool ne résout pas le chemin Windows du dépôt ; le grep a été lancé via `wsl.exe -d Ubuntu-24.04`. Commande et résultat inchangés.
- Non modifié : `Statut` de la tâche (reste « à faire », à mettre à jour par l'orchestrateur), aucun commit, aucun push.
