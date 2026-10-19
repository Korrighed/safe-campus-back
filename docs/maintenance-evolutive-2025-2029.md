# Maintenance évolutive 2025–2029 — Safe Campus

Statut : brouillon (20/10/2026)

| Élément | Valeur |
|---|---|
| Projet | Safe Campus — SAE501 |
| Période | 2025–2029 : réalisation (2025–2026), puis maintenance jusqu'à fin 2029 |
| Remplace | `plan-maintenance.md` (2026–2031, état au 30/07/2026) |
| Documents liés | [Cahier des charges techniques](cahier-des-charges-techniques.md) · [Sécurité et RGPD](securite-rgpd.md) · [Infrastructure](infra.md) · [Retours de tests](retours-tests.md) |

Les dates de fin de support (EOL, *end of life*) sont à revérifier à chaque revue trimestrielle sur [endoflife.date](https://endoflife.date). Une ligne marquée **à vérifier** n'a pas de source confirmée à la date du document.

---

## 1. Types de maintenance

| Type | Objet | Exemple Safe Campus |
|---|---|---|
| Corrective | Corriger une anomalie constatée | Bug signalé en recette, faille de sécurité |
| Préventive | Éviter une anomalie future | Mises à jour de dépendances, sauvegardes, supervision |
| Adaptative | Suivre l'évolution de l'environnement | Montée de version Node, PHP, Laravel, PostgreSQL |
| Évolutive | Ajouter ou modifier une fonctionnalité | Module de signalement, version anglaise |

---

## 2. Inventaire technique

État au 20/10/2026. Sources : `Safe-Campus-back/composer.json`, `Safe-Campus-front/package.json`, Dockerfiles.

| Composant | Version | Rôle |
|---|---|---|
| PHP | 8.4 | Runtime backend |
| Laravel | 13 | Framework backend |
| Filament | 3.3 | Back-office |
| Laravel Sanctum | 4 | Authentification API |
| PostgreSQL | 17 | Base de données |
| Nuxt | 4.5 | Framework frontend |
| Vue | 3.5 | Bibliothèque UI |
| Vue Router | 5 | Routage front |
| TypeScript | 5.7 | Typage front |
| Node.js | 22 (images `node:22`, `node:22-slim`) | Runtime frontend |
| Leaflet | 1.9 | Cartographie |

---

## 3. Calendrier de fin de support

| Composant | Fin de support | Statut au 20/10/2026 | Urgence |
|---|---|---|---|
| Nuxt 3 | 31/07/2026 | **Traité** : le front est passé à Nuxt 4 | — |
| Node.js 22 | 30/04/2027 | Maintenance LTS | 🟠 Planifier la migration en 2027 Q1 |
| Laravel 13 | Correctifs jusqu'à Q3 2027, sécurité jusqu'au 17/03/2028 | Support actif | 🟡 Suivre |
| PHP 8.4 | 31/12/2028 | Support actif | 🟡 Suivre |
| PostgreSQL 17 | 08/11/2029 | Support actif | 🟢 OK |
| Node.js 24 (cible) | 30/04/2028 — à vérifier | LTS | Prévoir la LTS suivante en 2028 |
| Nuxt 4 | Inconnu — à vérifier | Version courante | 🟡 Suivre l'annonce de Nuxt 5 |
| Filament 3 | Inconnu — à vérifier | Filament 4 publié | 🟠 Évaluer la migration en 2027 |

Sources de la v1 (juillet 2026) : [Nuxt 3 EOL](https://www.herodevs.com/blog-posts/nuxt-3-reaches-end-of-life-on-july-31-2026-what-are-your-options), [Node.js 22](https://dev.to/endoflifeai/nodejs-22-lts-eol-date-support-timeline-and-what-comes-next-30dm), [Laravel 13](https://endoflife.ai/laravel/13), [PHP](https://www.herodevs.com/blog-posts/php-end-of-life-dates-support-timeline-for-every-version-2026), [PostgreSQL](https://www.instaclustr.com/education/postgresql/postgres-versions-supported-releases-eol-dates-upgrades/).

---

## 4. Bilan 2025–2026 — réalisation

| Phase | Période | Réalisé |
|---|---|---|
| Cadrage | Avril 2026 | Dépôts, environnement Docker, choix techniques |
| Conception | Mai – juillet 2026 | MCD, contrats d'API, maquettes |
| Annuaire | Juillet – août 2026 | Taxonomie, contacts, médias, API annuaire, pages accueil / ressources / contact, carte |
| Histoires | Septembre 2026 | Modèle, éditeur, prévisualisation, lecteur interactif |
| Sécurisation | Septembre 2026 | Accès visiteur, inscription validée, rôle admin, journal d'audit |
| Intégration | Septembre – octobre 2026 | Pages légales, refonte CSS, migration Nuxt 4, police Cabin, guides du back-office |
| Recette | Octobre 2026 | Campagne de tests en cours : [retours-tests.md](retours-tests.md) |

Reste avant mise en production : mesures de sécurité S-01 à S-14 et actions MEP-01 à MEP-06 ([securite-rgpd.md](securite-rgpd.md)), choix de l'hébergeur.

---

## 5. Feuille de route 2026–2029

```mermaid
gantt
  title Feuille de route Safe Campus
  dateFormat YYYY-MM
  axisFormat %Y
  section Sécurité
  Mesures S-01 à S-14          :s1, 2026-11, 3M
  section Adaptative
  Node 22 vers Node 24         :a1, 2027-01, 3M
  Évaluation Filament 4        :a2, 2027-04, 3M
  Laravel version suivante     :a3, 2027-07, 6M
  Node LTS suivante            :a5, 2028-01, 3M
  PHP version suivante         :a4, 2028-04, 6M
  PostgreSQL version suivante  :a6, 2029-04, 6M
  section Évolutive
  Lot 1 (2027)                 :e1, 2027-02, 9M
  Lot 2 (2028)                 :e2, 2028-02, 9M
  Lot 3 (2029)                 :e3, 2029-02, 9M
```

| Période | Action | Type | Déclencheur |
|---|---|---|---|
| 2026 Q4 | Réaliser les mesures de sécurité priorité haute (S-01, S-02, S-03, S-06, S-10, S-11) | Corrective | Bloquant pour la mise en production |
| 2026 Q4 | Activer Dependabot ou Renovate sur les deux dépôts | Préventive | Aucun outil de veille en place (`.github/` absent) |
| 2026 Q4 | Mettre en place une CI : lint, typecheck, tests, build des images | Préventive | Aucune CI ; vérifications à la main |
| 2027 Q1 | Migrer Node 22 → Node 24 (images Docker du front) | Adaptative | EOL Node 22 au 30/04/2027 |
| 2027 Q2 | Évaluer la migration Filament 3 → 4 | Adaptative | Support Filament 3 à vérifier |
| 2027 Q3 – Q4 | Migrer Laravel 13 → version suivante | Adaptative | Fin des correctifs Laravel 13, sécurité jusqu'au 17/03/2028 |
| 2028 Q1 | Migrer vers la LTS Node suivante | Adaptative | EOL Node 24 (à vérifier) |
| 2028 Q2 – Q3 | Migrer PHP 8.4 → version suivante | Adaptative | EOL PHP 8.4 au 31/12/2028 |
| 2029 Q2 – Q3 | Migrer PostgreSQL 17 → version suivante (`pg_dump` / `pg_restore` ou `pg_upgrade`) | Adaptative | EOL PostgreSQL 17 au 08/11/2029 |
| Mensuel | Revue des dépendances mineures Composer / npm | Préventive | — |
| Trimestriel | Test de restauration, revue des comptes, revue des dates EOL | Préventive | — |
| Annuel (rentrée) | Rotation du mot de passe visiteur, révocation des jetons | Préventive | [securite-rgpd.md § 9](securite-rgpd.md#9-maintenance-de-la-sécurité) |

---

## 6. Évolutions fonctionnelles

Candidates issues du périmètre reporté de la v1, des retours de tests et des points ouverts du schéma. Le découpage en lots est une proposition à arbitrer avec l'établissement.

| ID | Évolution | Origine | Effort estimé | Lot proposé |
|---|---|---|---|---|
| EV-01 | Module de signalement anonyme : état (`nouveau`, `en_cours`, `traite`, `archive`), routage, anti-spam | [schema_bd.md, point 5](schema_bd.md#points-à-trancher) | L | 1 — dépend de la DNSI |
| EV-02 | Version anglaise (FR/EN) du site et du back-office | Hors périmètre v1, [retours-tests.md](retours-tests.md) | L | 2 |
| EV-03 | Horaires structurés (« ouvert maintenant ») : table `plage_horaire` | [schema_bd.md, point 3](schema_bd.md#points-à-trancher) | M | 2 |
| EV-04 | Recherche des contacts (endpoint dédié) | [textes-front.md § 5](textes-front.md#5-reste-à-trancher) | M | 1 |
| EV-05 | Page éditable « C'est quoi ? » de l'accueil (entité `page`) | [textes-front.md § 5](textes-front.md#5-reste-à-trancher) | S | 1 |
| EV-06 | Guide du back-office découpé par section, explication du rattachement média / contact | [retours-tests.md](retours-tests.md) | S | 1 |
| EV-07 | Fiches réflexives PDF des 18 documents (contenu à produire) | [textes-front.md § 5](textes-front.md#5-reste-à-trancher) | Contenu | 1 |
| EV-08 | Contrainte CHECK `Choix.issue` ⟺ `next_scene_id IS NULL` | [schema_bd.md, point 4](schema_bd.md#points-à-trancher) | XS | 1 |
| EV-09 | Partage de scène avec choix propres à chaque histoire | [schema_bd.md, point 1](schema_bd.md#points-à-trancher) | L | 3 — si le besoin apparaît |

Légende : XS < 2 h · S ≈ demi-journée · M ≈ 1 jour · L ≈ 2-3 jours.

---

## 7. Sauvegarde et restauration

À mettre en place avant la mise en production (S-06). Aucune stratégie n'existe dans les dépôts : seul le volume `scback-pgsql-prod` est déclaré.

| Élément | Cible |
|---|---|
| Base | `pg_dump` quotidien |
| Médias | Copie quotidienne du volume `storage/app` |
| Rétention | 30 jours glissants |
| Emplacement | Hors du serveur applicatif |
| Test de restauration | Trimestriel, sur un environnement séparé |
| Avant migration | Sauvegarde manuelle obligatoire avant tout `migrate --force` |

---

## 8. Supervision

Rien en place (S-07). À définir avec l'hébergeur retenu :

- Disponibilité : sonde HTTP sur le front et sur `/` du back (qui renvoie un JSON de statut).
- Erreurs applicatives : `storage/logs/laravel.log` (niveau `error` en production), avec alerte.
- Ressources : CPU, RAM, disque (volume PostgreSQL et médias).
- Sécurité : échecs de connexion répétés dans le journal `audit`.

---

## 9. Processus de maintenance

- **Correctif** : branche `hotfix/*` depuis `main`, fusion sur `main` et `dev`.
- **Évolution** : branche `feature/*` depuis `dev`, fusion `--no-ff` après validation.
- **Avant fusion** : tests back, lint et typecheck front au vert.
- **Mise en production** : sauvegarde, déploiement des images, commandes post-déploiement ([deploiement.md](deploiement.md)), recette de non-régression ([cahier des charges § 11.2](cahier-des-charges-techniques.md#112-scénarios-de-recette)).
- **Contenus** : vérification semestrielle de l'annuaire (numéros, horaires) par le webmaster.

---

## 10. Risques

| Risque | Gravité | Probabilité | Mesure |
|---|---|---|---|
| Premier déploiement en échec : `VISITEUR_PASSWORD` non transmis au conteneur | Bloquant | Certaine en l'état | S-10 |
| Serveur `artisan serve` en production : mono-processus, non prévu pour la charge | Élevé | Élevée | S-11 |
| Perte des médias et journaux à chaque redéploiement (pas de volume) | Élevé | Certaine en l'état | S-06 |
| Aucune sauvegarde définie | Élevé | Élevée si mise en prod sans correction | § 7 |
| API de lecture accessible sans jeton | Moyen | Élevée | S-01 |
| Aucun porteur de maintenance après la SAE | Élevé | Élevée | Question ouverte 1 |
| Absence de CI : régressions non détectées | Moyen | Moyenne | Feuille de route 2026 Q4 |
| Dépendances obsolètes sans veille automatique | Moyen | Moyenne | Dependabot / Renovate |

---

## Questions ouvertes

1. Qui maintient Safe Campus après la fin de la SAE, avec quel temps alloué ?
2. Quel niveau de service (SLA, *Service Level Agreement*) est attendu : délai de correction d'une anomalie bloquante, disponibilité cible ?
3. Quel budget l'établissement prévoit-il pour l'hébergement et la maintenance ?
4. La période 2025–2029 est-elle imposée par la SAE ? Le dépôt démarre le 20/04/2026 : la part 2025 ne couvre que le cadrage amont.
