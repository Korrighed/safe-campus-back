# Déploiement en production

> **Aucun hébergeur n'est retenu.** Le livrable est un jeu d'images Docker de production, déployables sur tout hôte Docker. Le reverse proxy et le certificat TLS sont à fournir en amont par l'hébergement choisi.

> `docker-compose.yml` (racine) est un stack de développement : `artisan serve`, bind mount du code source, Adminer exposé. Il n'est pas destiné à la production.

## Stack Docker prod

`docker-compose.prod.yml` + `docker/8.4/Dockerfile.prod`. Différences avec le stack dev :

- Code copié dans l'image au build, pas de bind mount.
- `composer install --no-dev --optimize-autoloader --no-scripts` au build, pas à chaque démarrage.
- Pas d'Adminer, pas de port PostgreSQL publié sur l'hôte : seul `sc_back` atteint `pgsql`.
- `container_name` et réseau (`scback_prod`) distincts du stack dev : les deux peuvent tourner en parallèle sans collision.
- Le conteneur ne parle que HTTP, sans nginx à l'intérieur, sur `PORT` (imposé par certains hébergeurs), sinon `APP_PORT`, sinon 8000. Le TLS est terminé par le reverse proxy de l'hébergement.
- Le point d'entrée est `docker/8.4/start-prod` : il recrée `public/storage`, puis, si `RUN_MIGRATIONS=true`, migre, et si `DEMO_CONTENT=true`, rejoue les seeders de démo. Les deux variables sont désactivées par défaut. Voir [deploiement-render.md](deploiement-render.md) pour un hébergeur sans commande post-déploiement.

> ⚠️ **À régler avant la mise en production** — voir [securite-rgpd.md § 10](securite-rgpd.md#10-mesures-complémentaires-à-mettre-en-œuvre) :
> - S-10 : les images exigent encore le compte visiteur du prototype. `VISITEUR_EMAIL` / `VISITEUR_PASSWORD` sont passés par `docker-compose.prod.yml` à `sc_back`, mais la migration `seed_visiteur` lève une exception si `VISITEUR_PASSWORD` est vide : le premier `migrate --force` échoue. Le portillon visiteur (compte, page de connexion, middleware) est à retirer au lancement officiel.
> - S-11 : `Dockerfile.prod` lance `php artisan serve`, serveur de développement mono-processus. À remplacer par PHP-FPM + nginx ou FrankenPHP.
> - S-06 : les volumes `scback-storage-prod` (`storage/app`) et `scback-logs-prod` (`storage/logs`) existent dans `docker-compose.prod.yml`. Il reste la sauvegarde quotidienne hors serveur (voir [Logs d'audit](#logs-daudit)).

## Variables d'environnement

À saisir dans la configuration de l'hébergement. Aucun fichier `.env` n'est copié dans l'image. Liste exacte : [.env.production.example](../.env.production.example).

| Variable | Valeur dev | Valeur prod |
|---|---|---|
| `APP_ENV` | `local` | `production` (fixé dans `docker-compose.prod.yml`) |
| `APP_DEBUG` | `true` | `false` (fixé dans `docker-compose.prod.yml`). Sinon : stacktraces et données sensibles exposées. |
| `APP_KEY` | généré en local | Généré **une seule fois** (`php artisan key:generate --show`), jamais régénéré. Le régénérer invalide sessions, cookies et données chiffrées existantes. |
| `APP_URL` | `http://localhost:8000` | URL publique en HTTPS |
| `DB_CONNECTION` | `pgsql` | `pgsql` (fixé dans `docker-compose.prod.yml`). Aucun `.env` dans l'image : sans lui, Laravel retombe sur `sqlite`. |
| `DB_DATABASE` | `sc_back` | Nom de base spécifique |
| `DB_USERNAME` | `sail` | Utilisateur de la base. Créé superutilisateur par l'image PostgreSQL : utilisateur dédié à prévoir (S-18). |
| `DB_PASSWORD` | `password` | Mot de passe fort (min. 20 caractères, aléatoire) |
| `LOG_LEVEL` | `debug` | `error` (fixé dans `docker-compose.prod.yml`) |
| `SANCTUM_STATEFUL_DOMAINS` | `localhost:3000,localhost` | Domaine public du front, sans schéma. |
| `VISITEUR_EMAIL` / `VISITEUR_PASSWORD` | `visiteur@safe-campus.nc` / valeur du `.env` | Portillon visiteur du prototype, créé au premier `migrate`. Mot de passe changé entre deux événements. Sans objet après le lancement officiel (S-10). |
| `ADMIN_EMAIL` / `ADMIN_PASSWORD` | `admin@safe-campus.nc` / `password` | Premier compte admin, créé au premier `migrate`. Mot de passe fort obligatoire (14 caractères, majuscule, minuscule, caractère spécial), sinon la migration échoue. Peut être retiré une fois le compte créé. |
| `SESSION_SECURE_COOKIE` | — | `true` par défaut dans `docker-compose.prod.yml` (S-13). À mettre à `false` seulement pour tester l'image en HTTP sur localhost : le cookie de `/admin` ne passerait pas. |
| `TRUSTED_PROXIES` | vide | `*` quand le conteneur n'est joignable que par le reverse proxy de l'hébergeur ; sinon IP/CIDR du proxy. Vide par défaut : derrière un proxy TLS, Laravel génère alors des URL `http://` (assets de `/admin` cassés), l'IP du proxy remplace l'IP client dans le journal d'audit, et la limite de débit de `/api/login` s'applique à tous les visiteurs ensemble. |
| `CORS_ALLOWED_ORIGINS` | `http://localhost:3000` | Origines complètes du front (schéma + hôte), séparées par des virgules. Lue par `config/cors.php`. |

## Commandes post-déploiement

`migrate`, `filament:upgrade` et `package:discover` ne tournent pas au build : le `.env` et `APP_KEY` n'existent pas à ce moment-là (voir `Dockerfile.prod`). Ils s'exécutent dans le conteneur `sc_back` après chaque déploiement, comme hook post-déploiement de l'hébergement ou à la main :

```bash
php artisan package:discover --ansi
php artisan filament:upgrade
php artisan migrate --force
php artisan storage:link
```

> `public/storage` (symlink) est exclu du build (`.dockerignore`). Sans `storage:link`, les médias uploadés (images, PDF) renvoient 404 côté front.

> `php artisan migrate --force` bypass la confirmation interactive. Ne jamais l'exécuter sur une base de production sans sauvegarde préalable. Ne jamais inclure `db:seed` (données de démonstration) : réservé au dev.
>
> `key:generate` ne fait **pas** partie de ces commandes. Il s'exécute une seule fois, à la main, avant le tout premier déploiement. La valeur générée devient `APP_KEY` (voir tableau ci-dessus).

## Front

Déploiement du front : [README de Safe-Campus-front § Déploiement](../../Safe-Campus-front/README.md#déploiement-prod). `NUXT_PUBLIC_API_BASE` doit pointer sur l'URL publique HTTPS de ce back.

## Tâches planifiées

`comptes:purger-inactifs` (quotidienne) supprime les comptes sans connexion depuis 3 ans (voir [securite-rgpd.md § 14](securite-rgpd.md)). Le conteneur ne lance que `artisan serve` : aucun planificateur n'y tourne. Cron de l'hôte, chaque minute :

```bash
* * * * * docker exec SC_Back_Prod php artisan schedule:run >> /dev/null 2>&1
```

Contrôle à blanc : `docker exec SC_Back_Prod php artisan comptes:purger-inactifs --simulation`.

## Logs d'audit

Qui a fait quoi, quand, depuis quelle IP : canal `audit` (`config/logging.php`), distinct de `laravel.log`.

- **Fichiers** : `storage/logs/audit-AAAA-MM-JJ.log`, un par jour, **365 jours** de conservation (purge automatique par Laravel).
- **Format** : une entrée JSON par ligne (`message`, `context`, `datetime` en UTC).
- **Contenu** : création, modification (avant/après) et suppression de tout modèle métier. Connexion, échec de connexion (email tenté), déconnexion, inscription. Transfert d'histoires à la suppression d'un rédacteur. Purge d'un compte inactif (`User.purge_inactivite`) ou ignorée (`User.purge_ignoree`). Jamais de mot de passe ni de jeton. Textes longs tronqués à 200 caractères.
- **Niveau** : `info`, fixe, indépendant de `LOG_LEVEL` (qui vaut `error` en production).
- **Écrit après commit** : une action annulée (transaction en échec) n'est pas tracée.
- **Non tracé** : les rattachements N-N (histoire ↔ sous-thèmes, médias ↔ sous-thèmes), qui ne passent pas par les événements de modèle.
- **Consultation** : accès serveur uniquement, aucun écran dans le back-office.

```bash
docker exec SC_Back_Prod ls storage/logs
docker exec SC_Back_Prod tail -f storage/logs/audit-2026-09-28.log
```

> `storage/logs` et `storage/app` (médias uploadés) sont montés sur les volumes nommés `scback-logs-prod` et `scback-storage-prod` : un redéploiement ne les efface plus. `Dockerfile.prod` crée ces dossiers et `storage/framework/*` avec le propriétaire `scback` (ils sont exclus par `.dockerignore`) ; sans cela Blade ne peut pas compiler les vues. Ces volumes ne sont pas sauvegardés : la sauvegarde hors serveur reste à mettre en place.
