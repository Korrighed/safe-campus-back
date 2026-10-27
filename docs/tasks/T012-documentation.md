# T012 — Mettre la documentation à jour

Statut : à faire
Story : tous · Exigences : F-01 à F-11, NF-02, NF-03
Dépend de : T001 à T011
Parallélisable : non
Exécutant : executor

## Objectif

Les documents décrivent cinq rôles, le circuit de relecture, le cloisonnement et les nouvelles règles du journal d'audit. Aucun document ne dit plus que le rédacteur publie seul.

## Fichiers à lire

- `docs/PRD.md`, `docs/TRD.md` — source des règles (§ 7 du TRD pour la matrice).
- `docs/schema_bd.md` — § Rôles (~l. 566-600), l. 439, l. 722-724, l. 760.
- `docs/securite-rgpd.md` — § 5 (~l. 118-136), § 7 (~l. 169-186), l. 93, S-16 (~l. 285).
- `docs/cahier-des-charges-techniques.md` — l. 47, tableau des droits (~l. 125-140), l. 152, l. 161-167, l. 503.
- `docs/deploiement.md` — l. 78-86.
- `docs/README.md`.

## Fichiers cibles

| Fichier | Action | Ce qui change |
|---|---|---|
| `docs/schema_bd.md` | modifier | Rôles, matrice, administration des comptes, enum. |
| `docs/securite-rgpd.md` | modifier | Matrice, garde-fous, journalisation. |
| `docs/cahier-des-charges-techniques.md` | modifier | Personas, droits, circuit. |
| `docs/deploiement.md` | modifier | Contenu du journal d'audit. |
| `docs/README.md` | modifier | Deux lignes d'index. |

## Étapes

1. `schema_bd.md` :
   - § « Rôles » : « Quatre rôles » → « Cinq rôles » ; ajouter `relecteur` au tableau des ressources avec la colonne `relecteur` (Histoires / Scènes / Choix : ✓ en lecture et correction de toutes les histoires, sans création ; Médias ✗ ; autres ✗). La cellule Histoires du rédacteur devient « ✓ (les siennes, en brouillon) ». Ajouter une phrase : l'accès d'un rédacteur est limité aux histoires dont il est l'auteur (requête filtrée, 404 sinon).
   - § « Administration des comptes » : l'admin attribue `webmaster`, `redacteur`, `relecteur` ou `admin` ; la reprise des histoires peut aller à un rédacteur ou à un relecteur.
   - l. 439 (`Etat`) : ajouter qui peut poser quel état (rédacteur : `brouillon` → `relecture` sur ses histoires en brouillon ; relecteur : tous les états).
   - l. 722-724 : `User.Role` = `webmaster`, `redacteur`, `relecteur`, `visiteur`, `admin`. l. 760 : « Cinq rôles fixes ».
2. `securite-rgpd.md` :
   - § 5 : ajouter la colonne `relecteur` à la matrice (Histoires / Scènes / Choix : ✓ ; Accès au back-office : ✓) ; l. 93 : `canAccessPanel()` accepte aussi `relecteur`.
   - § 5.1 : « transférer ses histoires à un autre rédacteur ou à un relecteur ». Ajouter deux garde-fous : un rédacteur ne voit que ses histoires et ne modifie que celles en brouillon ; un rédacteur ne peut pas poser `valide` ni `publie` (validé côté serveur).
   - § 7, ligne « Événements » : ajouter `Histoire.etat_change`, `Histoire.scene_ajoutee`, `scene_retiree`, `scene_deplacee`, `depart_defini`, `sous_themes_modifies`, `Histoire.acces_refuse`. Ligne « Exclusions » : « Jamais de mot de passe ni de jeton. Jamais le titre ni le texte d'une scène ou d'un choix : seuls l'identifiant et le nom des champs modifiés sont écrits. Autres textes longs tronqués à 200 caractères. » Ajouter une note : les entrées écrites avant cette règle peuvent contenir les 200 premiers caractères d'un dialogue, jusqu'à leur purge à 365 jours.
   - Remplacer la note S-16 : les rattachements des histoires (scènes, sous-thèmes) sont désormais tracés par des événements explicites ; les autres (média ↔ sous-thème, contact ↔ sous-thème) ne le sont pas. Mettre à jour la ligne S-16 du tableau des écarts (statut : partiellement traité).
3. `cahier-des-charges-techniques.md` :
   - l. 47 : Rédacteur → « Écrire et illustrer des histoires interactives, les soumettre en relecture. » ; ajouter une ligne Relecteur → « Relire, corriger, valider, publier ou renvoyer en brouillon les histoires. Back-office, rôle relecteur. »
   - Tableau des droits : ajouter la colonne ou les lignes du relecteur selon la forme du tableau (lire d'abord) ; Histoires / Scènes / Choix du rédacteur : « Créer, lire et modifier les siennes en brouillon ». La ligne « Changer l'état d'une histoire, jusqu'à « publié » » : rédacteur « Non (brouillon → relecture seulement) », relecteur « Oui ».
   - Remplacer le paragraphe l. 137 « Le circuit… est déclaratif… » par : le circuit brouillon → relecture → validé → publié est contrôlé : le rédacteur ne peut que soumettre en relecture ; le relecteur valide, publie ou renvoie en brouillon.
   - l. 152 et 161-167 : « Rôles attribuables : webmaster, rédacteur, relecteur, administrateur » ; le repreneur peut être un rédacteur ou un relecteur ; l. 503 : ajouter le relecteur dans les sessions de prise en main.
4. `deploiement.md` l. 78-86 : mettre à jour « Contenu » (nouveaux événements, règle « aucun texte de scène ou de choix ») et « Non tracé » (seuls les rattachements médias ↔ sous-thèmes et contacts ↔ sous-thèmes).
5. `docs/README.md` : sous « Références techniques », ajouter deux lignes : `[PRD.md](PRD.md)` — « Rôle relecteur et cloisonnement des rédacteurs : besoins » et `[TRD.md](TRD.md)` — « Rôle relecteur : matrice des droits, audit, décisions ».
6. Passer sur les documents modifiés avec `grep -rniE "rédacteur|redacteur" docs/*.md` et corriger toute phrase devenue fausse (par exemple « le rédacteur publie »). Ne pas toucher `docs/PRD.md`, `docs/TRD.md`, `docs/IMPLEMENTATION-PLAN.md` ni `docs/tasks/`.

## Critères d'acceptation

- [ ] AC-01 : Étant donné `grep -rn "Quatre rôles" docs/`, alors aucun résultat.
- [ ] AC-02 : Étant donné `grep -rn "le rédacteur fixe lui-même" docs/`, alors aucun résultat.
- [ ] AC-03 : Chaque événement d'audit du TRD § 5 (T-09 à T-12) figure dans `securite-rgpd.md` § 7.
- [ ] AC-04 : Les tableaux Markdown modifiés restent bien formés (même nombre de colonnes sur chaque ligne).

## Vérification

```bash
grep -rn "Quatre rôles" docs/
grep -rn "le rédacteur fixe lui-même" docs/
grep -rln "relecteur" docs/*.md
```

Parcours navigateur : aucun (documentation).

## Hors périmètre

- Aucune modification de code.
- Pas de régénération des PDF (livrables générés à part).
- Ne pas réécrire les parties des documents sans rapport avec les rôles ou l'audit.

## Notes d'exécution
