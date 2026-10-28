# Intégration continue (GitHub Actions)

Les deux dépôts (`safe-campus-back`, `safe-campus-front`) ont un workflow `.github/workflows/ci.yml` nommé **CI**. Aucun déploiement n'est automatique : la CI vérifie, Render déploie à la main.

## Déclencheurs et jobs

| Événement | Jobs lancés |
|---|---|
| Push sur `dev` ou `main` | `verif` |
| Pull request vers `dev` ou `main` | `verif` |
| Pull request vers `main`, push sur `main` | `verif` **et** `approfondi` |
| Lancement manuel (onglet Actions, *Run workflow*) | `verif` ; **et** `approfondi` si la branche choisie est `main` |

`verif` doit être vert avant de pousser `dev`. `approfondi` est la vérification renforcée avant `main`.

## Contenu des jobs

| | `verif` | `approfondi` |
|---|---|---|
| **Back** | Pint (style), migrations et seeders sur PostgreSQL 17, PHPStan niveau 5 sans baseline, tests PHPUnit | `composer audit --locked`, build de `docker-compose.prod.yml` avec des valeurs jetables, démarrage par `docker/8.4/start-prod` (migrations et seeders de démo), commandes post-déploiement, contrôle HTTP de `/up` et `/api/themes`, fichiers de média servis en HTTP 200, scan Trivy (failles CRITICAL corrigeables) |
| **Front** | `npm ci`, build, typecheck, lint | `npm audit` (bloquant en *critical*, affichage seul pour *high*), build de l'image de prod, démarrage et contrôle de `/mentions-legales`, scan Trivy |

Les valeurs du job `approfondi` du back (clé, mots de passe, base) sont générées à l'exécution et jetables. La CI n'utilise aucun secret GitHub.

## Réglages GitHub à poser sur chaque dépôt

| Où | Réglage |
|---|---|
| Settings, Actions, General, *Actions permissions* | Autoriser toutes les actions, ou les seules actions GitHub plus `shivammathur/setup-php@*` et `aquasecurity/*`. Ne **pas** cocher « Require actions to be pinned to a full-length commit SHA » : les actions sont référencées par étiquette (sauf Trivy). |
| *Approval for running fork pull request workflows* | Require approval for all external contributors |
| *Workflow permissions* | Read repository contents and packages permissions |
| *Allow GitHub Actions to create and approve pull requests* | Décoché |
| Settings, Code security | Secret scanning et push protection activés |

Un fork démarre avec ses workflows désactivés. Ils se mettent en route quand le workflow existe sur la branche par défaut (`main`).

## Ruleset de `main`

| Règle | Choix |
|---|---|
| Restrict deletions, Block force pushes | Oui |
| Require a pull request before merging | Oui, méthode de fusion *Merge* uniquement (l'historique est conservé) |
| Require status checks to pass | Oui : `verif` et `approfondi`, avec « up to date before merging » |
| Require linear history | **Non** : le git flow fusionne en `--no-ff` |
| Require signed commits | Non : les commits ne sont pas signés |

Les checks ne sont sélectionnables qu'après avoir tourné une fois. Le nom saisi doit être identique à celui du job (`verif`, `approfondi`) : une faute de frappe bloque toute fusion.

Avec « up to date » coché, `dev` doit contenir `main` avant une nouvelle PR : après une fusion, `git checkout dev && git merge origin/main`.

## Lire un échec

Onglet **Actions**, workflow **CI**, exécution, job, puis étape en rouge. *Re-run failed jobs* relance sans pousser de code. Une annotation « Process completed with exit code 1 » sur l'audit npm *high* du front est normale : l'étape est volontairement non bloquante.

## État et limites

- Trivy : l'action `aquasecurity/trivy-action` est épinglée par commit (v0.36.0). Ses anciennes étiquettes sans `v` ont été supprimées après un incident de chaîne d'approvisionnement ; une référence `@0.28.0` fait échouer le job avant sa première étape.
- Les autres actions (`checkout`, `cache`, `setup-php`, `setup-node`) sont référencées par étiquette. GitHub signale que celles qui tournent sous Node 20 sont forcées sur Node 24 : sans effet pour l'instant.
- Le front n'a pas de tests automatisés : `verif` y contrôle le build, le typecheck et le lint.
- Dependabot n'est pas configuré.
- La baseline PHPStan a été supprimée : toute nouvelle erreur de niveau 5 fait échouer `verif`.
