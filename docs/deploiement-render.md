# Déploiement de la démo : Render et Neon

> Démo publique temporaire. Ce n'est pas l'infrastructure de production finale (voir [deploiement.md](deploiement.md) et [securite-rgpd.md](securite-rgpd.md) § 8).

| Brique | Rôle |
|---|---|
| Render, 2 services web (plan gratuit) | Front Nuxt et back Laravel, en région Oregon, HTTPS et reverse proxy fournis |
| Neon (Postgres 17, région `us-west-2`) | Base de données, même région que Render |
| Variables d'environnement de Render | Seul endroit où vivent les secrets : rien dans le dépôt (`.env.*` ignorés par git et par Docker) |

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
| `REDACTEUR_DEMO_EMAIL`, `REDACTEUR_DEMO_PASSWORD` | Requis si `DEMO_CONTENT=true`. Mot de passe aléatoire de 30 caractères ou plus, que personne n'a besoin de connaître | **oui** (mot de passe) |
| `RUN_MIGRATIONS` | `true` pour migrer au démarrage. Inutile si la base est déjà migrée | non |

## Variable du front

| Variable | Valeur |
|---|---|
| `PORT` | `3000` |
| `NUXT_PUBLIC_API_BASE` | URL publique du back, `https://...onrender.com` |

## Démarrage du back (`docker/8.4/start-prod`)

1. Écoute sur `$PORT`, sinon `$APP_PORT`, sinon 8000.
2. Recrée `public/storage` (exclu de l'image).
3. Si `RUN_MIGRATIONS=true` : `migrate --force`.
4. Si `DEMO_CONTENT=true` : rejoue `MediaSeeder`, `RedacteurDemoSeeder` et les trois seeders d'histoires. Ils sont idempotents et recopient les fichiers sur le disque du conteneur.
5. Sert l'application.

Une tâche qui échoue est signalée dans les journaux (`AVERTISSEMENT`) mais n'empêche pas le serveur de démarrer.

## Limites du plan gratuit

| Limite | Conséquence |
|---|---|
| Mise en veille après 15 minutes sans trafic | Le premier appel suivant prend environ une minute. Ouvrir le site avant une présentation. |
| Pas de disque persistant | Les médias de démo sont recréés à chaque démarrage. **Les fichiers téléversés depuis `/admin` disparaissent** au redémarrage. |
| 512 Mo de mémoire, 0,1 CPU | `PHP_CLI_SERVER_WORKERS=2`. Surveiller *Metrics* : un dépassement redémarre le service. |
| Pas de shell, pas de tâche ponctuelle | Migrations et seeders passent par `start-prod`. |
| Pas de planificateur | La purge des comptes inactifs à 3 ans (S-19) ne tourne pas. Sans objet pour une démo d'une semaine. |
| 750 heures gratuites par mois, partagées | Deux services allumés en continu les épuisent en environ 15 jours |

## Données personnelles de la démo

`DEMO_CONTENT=true` publie l'annuaire de contacts (`ContactSeeder`, structures d'aide de Nouvelle-Calédonie) et trois histoires de démonstration. Vérifier avant la mise en ligne que cette publication est acceptable. Le compte rédacteur de démo créé par `DEMO_CONTENT` n'est pas destiné à servir : son mot de passe doit être aléatoire et inconnu.

## Vérifications après déploiement

```bash
curl -s -o /dev/null -w "%{http_code}\n" https://<back>/up
curl -s https://<back>/api/themes | head -c 200
curl -sI https://<back>/up | grep -i strict-transport
curl -sI -H "Origin: https://<front>" https://<back>/api/themes | grep -i access-control-allow-origin
```

L'URL d'un fichier de média, lue dans `/api/sous-themes/alcool` (champ `documents[].url`), doit répondre 200.

## Retour arrière

Supprimer les deux services Render ; la base Neon se réinitialise avec `DROP SCHEMA public CASCADE; CREATE SCHEMA public;`.
