# Infrastructure — Stats & Estimation prod

## Environnement de mesure

- Machine hôte : Docker Desktop sur WSL 2 (noyau 6.6, 8 vCPU, 7.76 GiB RAM allouées à Docker)
- Stack : SC_Back (Laravel 13, PHP 8.4) + SC_Front (Nuxt 3, Node 22) + SC_Postgres (PostgreSQL 17) + SC_Adminer
- Base de données de développement quasi-vide

---

## Consommation runtime

| Container | CPU | RAM | Limite compose |
|---|---|---|---|
| SC_Back | 0.04% | 93 MiB | 2.5 GiB |
| SC_Front | 0.09% | 275 MiB | 2 GiB |
| SC_Postgres | 0.00% | 24 MiB | 2 GiB |
| SC_Adminer | 0.00% | 20 MiB | 256 MiB |
| **Total** | | **~412 MiB** | **~6.75 GiB** |

Mesure au repos, aucune requête en cours. `SC_Front` domine : le serveur de dev Vite garde le graphe de modules en mémoire.

---

## Tailles des images Docker

> Mesures antérieures au split des `docker-compose.yml` (back/front séparés) : `sc_front` est bâti par une stack compose distincte, préfixe d'image `safe-campus-front-*` désormais.

| Image | Tag | Taille |
|---|---|---|
| safe-campus-back-sc_back | latest | 1.01 GB |
| safe-campus-front-sc_front | latest | 1.64 GB |
| postgres | 17 | 645 MB |
| adminer | latest | 170 MB |

`sc_front` est bâtie sur `node:22` (1.64 GB) : elle n'ajoute quasiment rien à sa base.

### Composition de l'image `sc_back` (1.01 GB)

| Couche | Poids |
|---|---|
| Base `php:8.4-cli` | ~825 MB |
| Extensions PHP + client PostgreSQL + libs système + Xdebug | 182 MB |
| Composer | 3.65 MB |
| `php.ini`, `start-container`, utilisateur `scback` | < 1 MB |

---

## Stockage volumes

| Volume | Contenu | Taille |
|---|---|---|
| `safe-campus-back_scback-pgsql` | Données PostgreSQL | 73 MB |
| `safe-campus-front_sc_front_node_modules` | Dépendances Node du front | 244 MB |

---

## Estimation production

### Ressources serveur

| Ressource | Minimum | Recommandé |
|---|---|---|
| RAM | 512 MB | 1–2 GB |
| CPU | 1 vCPU | 2 vCPU |
| Disque OS + app | 5 GB | 10 GB |
| Disque PostgreSQL | 5 GB | 20 GB+ |

### Hypothèses

- Laravel en production : **128–256 MB RAM** (PHP-FPM, sans Xdebug). L'image actuelle lance encore `php artisan serve` : à remplacer (S-11, [securite-rgpd.md](securite-rgpd.md#10-mesures-complémentaires-à-mettre-en-œuvre))
- PostgreSQL : **256 MB–1 GB RAM** selon charge et taille des données
- Nuxt en production : build statique ou SSR Node — le serveur de dev Vite et ses 275 MB n'existent pas
- Pas de Redis dans le stack — à prévoir si queues volumineuses

### Exemples de sizing cloud (indicatif)

| Profil | Specs | Usage |
|---|---|---|
| Petit (staging / faible trafic) | 1 vCPU / 1 GB RAM / 20 GB SSD | < 50 utilisateurs simultanés |
| Moyen | 2 vCPU / 2 GB RAM / 40 GB SSD | 50–500 utilisateurs |
| Séparation DB | App : 2 vCPU / 2 GB + DB : 2 vCPU / 4 GB | Charge variable, backups indépendants |

---

## Nettoyage disque local

Les rebuilds successifs laissent des images et un cache de build orphelins.

```bash
docker system df                  # état actuel
docker image prune -f             # images sans tag
docker builder prune -f           # cache de build
```

Nettoyage global, tous projets confondus — détruit les volumes non utilisés :

```bash
docker system prune --volumes
```
