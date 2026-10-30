# Déploiement de la démo : Render et Neon

> Démo publique temporaire. Ce n'est pas l'infrastructure de production finale (voir [deploiement.md](deploiement.md) et [securite-rgpd.md](securite-rgpd.md) § 8). Les risques et limites assumés sont au § 8.2 de ce dernier.

| Brique | Rôle |
|---|---|
| Render, 2 services web (plan gratuit) | Front Nuxt et back Laravel, région Oregon, HTTPS et reverse proxy fournis |
| Neon (PostgreSQL 17, région `us-west-2`) | Base de données, même région que Render |
| Variables d'environnement de Render | Seul endroit où vivent les secrets : rien dans le dépôt (`.env.*` ignorés par git et par Docker) |

Les dépôts publics `safe-campus-back` et `safe-campus-front` sont des forks de ceux de l'équipe. Render déploie la branche `main`.

## Ordre de mise en ligne

1. Fork des deux dépôts, **toutes les branches comprises**.
2. Settings, Actions : actions autorisées, approbation des workflows pour tous les contributeurs externes, jeton en lecture seule ([ci.md](ci.md)).
3. Premier passage de la CI ; ruleset de `main` ensuite.
4. Neon : projet PostgreSQL 17 en `us-west-2`, **connexion directe** (sans `-pooler`).
5. Render : back puis front, tous deux en **Oregon**. La région d'un service ne se change pas après coup.
6. Variables d'environnement, puis *Manual Deploy* de chaque service.
7. Vérifications (voir plus bas).

## Services Render

| Champ | Back (`safe-campus-back`) | Front (`safe-campus-front`) |
|---|---|---|
| Runtime | Docker | Docker |
| Branche | `main` | `main` |
| Région | Oregon | Oregon |
| Dockerfile | `./docker/8.4/Dockerfile.prod` | `./docker/Dockerfile.prod` |
| Health Check Path | `/up` | `/mentions-legales` |
| Instance | Free | Free |
| Auto-Deploy | Off | Off |

Le health check du front ne doit pas être `/healthz` (valeur proposée par Render) : cette route n'existe pas, le déploiement serait refusé.

## Variables du back

| Variable | Valeur | Secret |
|---|---|---|
| `PORT` | `8000` (facultatif : `start-prod` lit aussi `APP_PORT`) | non |
| `APP_ENV`, `APP_DEBUG`, `LOG_LEVEL` | `production`, `false`, `error` | non |
| `APP_KEY` | `base64:` + 32 octets aléatoires, générée une fois, jamais régénérée | **oui** |
| `APP_URL` | URL publique du back, `https://...onrender.com` | non |
| `DB_CONNECTION`, `DB_PORT`, `DB_SSLMODE` | `pgsql`, `5432`, `require` | non |
| `DB_HOST`, `DB_DATABASE`, `DB_USERNAME` | Hôte **direct** Neon (sans `-pooler`), base, rôle | non |
| `DB_PASSWORD` | Mot de passe du rôle Neon | **oui** |
| `CORS_ALLOWED_ORIGINS` | URL du front, avec `https://`, sans `/` final | non |
| `SANCTUM_STATEFUL_DOMAINS` | Hôte du front, sans `https://` | non |
| `TRUSTED_PROXIES` | `*` : sans lui, les assets de `/admin` sortent en `http://` et HSTS n'est pas posé | non |
| `SESSION_SECURE_COOKIE` | `true` | non |
| `PHP_CLI_SERVER_WORKERS` | `2` (512 Mo de mémoire) | non |
| `ADMIN_EMAIL`, `ADMIN_PASSWORD` | Premier admin. Mot de passe : 14 caractères minimum, majuscule, minuscule, caractère spécial | **oui** (mot de passe) |
| `VISITEUR_EMAIL`, `VISITEUR_PASSWORD` | Portillon du prototype. Mot de passe long, partagé aux visiteurs | **oui** (mot de passe) |
| `DEMO_CONTENT` | `true` pour recréer médias et histoires de démo à chaque démarrage | non |
| `REDACTEUR_DEMO_EMAIL` | E-mail d'un compte **rédacteur existant** (voir ci-dessous) | non |
| `REDACTEUR_DEMO_PASSWORD` | Aléatoire, 30 caractères ou plus. Ne sert que si le compte est recréé | **oui** |
| `RUN_MIGRATIONS` | `true` pour migrer au démarrage. Inutile si la base est déjà migrée | non |

## Variables du front

| Variable | Valeur | Remarque |
|---|---|---|
| `PORT` | `3000` | |
| `NUXT_PUBLIC_API_BASE` | URL publique du back | Adresse d'API utilisée par le **navigateur**. Lue au démarrage. |
| `NUXT_API_BASE_INTERNAL` | URL publique du back | **Obligatoire sur Render** : adresse d'API utilisée par le **rendu côté serveur**. Sans elle, le serveur interroge `http://localhost:8000` (valeur figée au build) et toutes les pages de contenu renvoient 404 « Rubrique introuvable ». |

## Démarrage du back (`docker/8.4/start-prod`)

1. Écoute sur `$PORT`, sinon `$APP_PORT`, sinon 8000.
2. Recrée `public/storage` (exclu de l'image).
3. Si `RUN_MIGRATIONS=true` : `migrate --force`.
4. Si `DEMO_CONTENT=true` : rejoue `MediaSeeder`, `RedacteurDemoSeeder` et les neuf seeders d'histoires. Ils sont idempotents et recopient les fichiers sur le disque du conteneur.
5. Sert l'application.

Une tâche qui échoue est signalée dans les journaux (`AVERTISSEMENT`) mais n'empêche pas le serveur de démarrer.

## Contenu de démo : ce qu'il faut savoir

| Sujet | Comportement |
|---|---|
| Propriétaire des histoires | `HistoireDemoSeeder` les rattache au compte `REDACTEUR_DEMO_EMAIL`. Mettre l'adresse d'un rédacteur **existant** : le seeder le retrouve (`firstOrCreate`) au lieu d'en recréer un. Une adresse inexistante recrée un compte à chaque démarrage. |
| Modifications dans `/admin` | Les 9 histoires de démo (titre, état, texte, choix) retrouvent leurs valeurs du code à chaque démarrage. Elles sont en pratique en lecture seule. |
| Histoires créées dans `/admin` | Conservées en base ; leurs images, stockées sur le disque éphémère, disparaissent au redémarrage. |
| Fichiers téléversés dans `/admin` | Perdus au redémarrage (plan gratuit sans disque persistant). |

## Limites du plan gratuit

| Limite | Conséquence |
|---|---|
| Mise en veille après 15 minutes sans trafic | Le premier appel suivant prend environ une minute. Ouvrir le site avant une présentation. |
| Pas de disque persistant | Voir ci-dessus |
| 512 Mo de mémoire, 0,1 CPU | `PHP_CLI_SERVER_WORKERS=2`. Surveiller *Metrics* : un dépassement redémarre le service. |
| Pas de shell, pas de tâche ponctuelle | Migrations et seeders passent par `start-prod` |
| Pas de planificateur | La purge des comptes inactifs à 3 ans (S-19) ne tourne pas : elle exige un job planifié (`schedule:run` chaque minute, voir [deploiement.md § Tâches planifiées](deploiement.md#tâches-planifiées)). Non branché sur la démo d'une semaine, mais à prévoir chez l'hébergeur final. |
| 750 heures gratuites par mois, partagées | Deux services allumés en continu les épuisent en environ 15 jours |

## Gestion des secrets

- Chaque valeur se saisit dans l'onglet *Environment* du service Render. Rien n'est écrit dans git, dans un fichier compose ni dans un workflow.
- `APP_KEY` : générée une seule fois (`echo "base64:$(openssl rand -base64 32)"`). La changer invalide les sessions et les données chiffrées.
- Rotation d'un secret : modifier la variable dans Render, puis *Manual Deploy*. Pour le mot de passe Neon : réinitialiser le rôle dans la console Neon, puis mettre à jour `DB_PASSWORD`.
- Un secret collé dans un message ou un ticket est à révoquer et à remplacer.
- Doppler a été évalué puis écarté : sa synchronisation exige une clé d'API Render supplémentaire, sans gain pour deux services et une vingtaine de variables.

## Vérifications après déploiement

```bash
curl -s -o /dev/null -w "%{http_code}\n" https://<back>/up
curl -s https://<back>/api/themes | head -c 200
curl -sI https://<back>/up | grep -i strict-transport
curl -sI -H "Origin: https://<front>" https://<back>/api/themes | grep -i access-control-allow-origin
curl -s -o /dev/null -w "%{http_code}\n" -H "Cookie: sc_token=test" https://<front>/histoire/addictions/alcool
```

- L'URL d'un fichier de média, lue dans `/api/sous-themes/alcool` (champ `documents[].url`), doit répondre 200.
- La dernière commande teste le rendu côté serveur du front : 404 signifie que `NUXT_API_BASE_INTERNAL` manque.
- `/admin` : connexion avec le compte admin, cookies `secure` et `httponly`.

## Pièges rencontrés

| Symptôme | Cause |
|---|---|
| Pages de contenu du front en 404 | `NUXT_API_BASE_INTERNAL` absente (valeur figée au build) |
| Médias en 404 | `DEMO_CONTENT` absente, ou service pas redéployé depuis l'ajout de `start-prod` |
| Conteneur du back qui redémarre en boucle | Manifestes `bootstrap/cache/*.php` d'une machine de dev copiés dans l'image : exclus par `.dockerignore` |
| Assets de `/admin` en `http://` | `TRUSTED_PROXIES` absente |
| CORS refusé depuis le front | `CORS_ALLOWED_ORIGINS` absente ou différente de l'URL exacte du front |
| Images de scènes sans URL correcte | `APP_URL` absente (valeur par défaut `http://localhost`) |
| Déploiement du front refusé | Health Check sur `/healthz` au lieu de `/mentions-legales` |

## Retour arrière

Supprimer les deux services Render ; la base Neon se réinitialise avec `DROP SCHEMA public CASCADE; CREATE SCHEMA public;`.
