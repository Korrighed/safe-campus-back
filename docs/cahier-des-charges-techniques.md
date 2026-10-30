# Cahier des charges techniques — Safe Campus

| Élément | Valeur |
|---|---|
| Projet | Safe Campus — SAE501, Université de la Nouvelle-Calédonie |
| Version | 1.0.0 |
| Date | 30/10/2026 |
| Périmètre | Site étudiant (Nuxt 4) · API et back-office (Laravel 13 / Filament 3) |
| Livrables | Site étudiant, back-office d'administration, API REST JSON, images Docker de production |

Les exigences sont classées en quatre familles :

- les **exigences fonctionnelles** décrivent ce que le système fait ;
- les **exigences non fonctionnelles** décrivent avec quelle qualité il le fait ;
- les **exigences d'exploitation** décrivent comment il est hébergé et maintenu ;
- les **contraintes** sont les limites imposées au projet.

Chaque exigence est vérifiée par un parcours de recette, listé en tête de la section 3 et schématisé à la section 11. Son état est donné au 20/10/2026 : conforme, partiel ou non conforme.

---

## 1. Contexte

Safe Campus est réalisé dans le cadre de la SAE501 pour le campus de l'Université de la Nouvelle-Calédonie. Le périmètre fonctionnel est fourni par la SAE. Le périmètre technique est laissé au choix de l'équipe projet.

Les étudiants confrontés à une addiction, à une souffrance psychique ou à des violences ne savent pas toujours vers qui se tourner. L'information existe, mais elle est dispersée, peu adaptée au mobile et rarement contextualisée pour la Nouvelle-Calédonie. Safe Campus la centralise dans une plateforme sobre, consultable discrètement depuis un téléphone. Le personnel du campus en maintient le contenu, sans développeur.

| Thème | Sous-thèmes (fiches) |
|---|---|
| Conduites addictives | Alcool, tabac, drogue |
| Santé mentale | Anxiété, dépression, burn-out |
| Violences | Violences sexistes, violences sexuelles, harcèlement |

### Objectifs

- Offrir aux étudiants un point d'entrée unique vers l'aide disponible sur le territoire.
- Rendre le personnel du campus autonome sur la mise à jour des contenus.
- Proposer un outil de prévention ludique (histoires interactives) en complément de l'information.

---

## 2. Acteurs

L'étudiant doit trouver vite et discrètement une information fiable et un contact joignable. Il consulte sur smartphone, souvent dans un moment de stress.

| Droit | Étudiant | Webmaster | Rédacteur | Relecteur | Administrateur |
|---|:-:|:-:|:-:|:-:|:-:|
| Consulter le site étudiant | ✓ | ✓ | ✓ | ✓ | ✓ |
| Se connecter au back-office | — | ✓ | ✓ | ✓ | ✓ |
| Gérer thèmes, fiches et annuaire | — | ✓ | — | — | — |
| Gérer les médias | — | ✓ | ✓ | — | — |
| Écrire une histoire et la soumettre en relecture | — | — | ✓ | — | — |
| Valider, publier ou renvoyer une histoire en brouillon | — | — | — | ✓ | — |
| Gérer les comptes et les rôles | — | — | — | — | ✓ |

Le détail des droits par ressource est donné au [§ 4](#4-matrice-des-droits). Le personnel utilise le back-office ponctuellement, sur poste de bureau, sans compétence technique.

---

## 3. Exigences fonctionnelles

### Parcours de recette

La colonne « Vérification » des exigences renvoie aux parcours ci-dessous, rejoués de bout en bout et schématisés au [§ 11.1](#111-parcours-de-recette).

| Parcours | Acteur et objet |
|---|---|
| 1 | Étudiant : accès et recherche d'aide |
| 2 | Étudiant : histoire interactive |
| 3 | Personnel : inscription et accès au back-office |
| 4 | Webmaster : mise à jour de l'annuaire |
| 5 | Rédacteur : écriture et soumission d'une histoire |
| 6 | Administrateur : suppression d'un compte |
| 7 | Conservation des comptes : demande d'effacement et purge à 3 ans |
| 8 | Information sur les données personnelles |
| Hors parcours | Affichage sur mobile, tablette et poste de bureau sans défilement horizontal. Mentions légales, CGU et page Données personnelles complètes ; cette dernière accessible sans mot de passe visiteur. |

### 3.1 Site étudiant

| Exigence | Vérification | État |
|---|---|---|
| Le site n'est accessible qu'après saisie du mot de passe partagé. Toute page redirige vers la page de connexion, puis revient à la page demandée. | Parcours 1 | Conforme |
| L'accueil présente les thèmes en sections et les sous-thèmes en cartes. | Parcours 1 | Conforme |
| La recherche couvre thèmes et sous-thèmes et reconnaît des synonymes (« clope » → tabac). | Parcours 1 | Conforme |
| La fiche ressources affiche l'article du sous-thème et ses fiches PDF, ouvertes dans un nouvel onglet. | Parcours 1 | Conforme |
| La fiche contacts liste les structures actives du sous-thème, dans l'ordre éditorial : téléphones cliquables, horaires, gratuité, anonymat, conditions d'accueil. | Parcours 1 | Conforme |
| La fiche contacts affiche une carte des structures géolocalisées. | Parcours 1 | Conforme |
| L'histoire interactive se joue scène par scène. Une fin défavorable affiche les contacts d'aide du thème. Le joueur peut recommencer. | Parcours 2 | Conforme |
| Une histoire non publiée n'est pas accessible. | Parcours 2 | Conforme |
| Mentions légales et conditions générales d'utilisation sont publiées. | Hors parcours | Partiel : éditeur, hébergeur et directeur de publication à compléter |

### 3.2 Back-office

| Exigence | Vérification | État |
|---|---|---|
| Gestion des thèmes et sous-thèmes : libellé, résumé, article, médias associés, activation. | Parcours 4 | Conforme |
| Gestion des contacts et de leurs téléphones, rattachement ordonné aux sous-thèmes. | Parcours 4 | Conforme |
| Géocodage de l'adresse d'un contact à la saisie, ajustement du point sur une carte. | Parcours 4 | Conforme |
| Bibliothèque de médias (image, vidéo, audio, document PDF) avec libellé de recherche. | Revue du back-office | Conforme |
| Gestion des histoires, scènes et choix. États : brouillon, relecture, validé, publié. | Parcours 2 | Conforme |
| Prévisualisation d'une histoire avant publication. | Parcours 5 | Conforme |
| Inscription publique. Le compte reste inactif jusqu'à l'attribution d'un rôle par un administrateur. | Parcours 3 | Conforme |
| Suppression d'un rédacteur avec transfert obligatoire de ses histoires. | Parcours 6 | Conforme |
| Suppression d'un compte par un administrateur sur demande de la personne (droit à l'effacement). | Parcours 7 | Conforme |
| Suppression automatique des comptes sans connexion depuis 3 ans, selon les règles du [§ 4.3](#43-cycle-de-vie-dun-compte-et-de-son-rôle). | Parcours 7 | À valider en recette |
| Guides contextuels pour le webmaster, le rédacteur et le relecteur, sur le tableau de bord et par section. | Revue du back-office | Conforme |
| Journal d'audit : création, modification, suppression, connexion, échec de connexion, inscription. Format JSON, un fichier par jour, conservé 365 jours, sans mot de passe ni jeton. | Parcours 4 | Conforme |

### 3.3 API

L'API de lecture est publique par conception.

| Route | Fonction | État |
|---|---|---|
| `POST /api/login` | Ouvre le portillon visiteur du prototype et renvoie un jeton d'accès. | Conforme |
| `GET /api/themes` | Thèmes et sous-thèmes en version légère, pour l'accueil. | Conforme |
| `GET /api/sous-themes/{ref}` | Détail d'un sous-thème : article, contacts actifs triés, téléphones, histoires publiées. | Conforme |
| `GET /api/histoires/{ref}` | Graphe complet d'une histoire publiée, en une seule requête. | Conforme |

Les modèles sont liés par `ref`, jamais par identifiant numérique. La forme JSON est définie par les classes de `app/Http/Resources/`. Chaque route a son propre contenu, sans données superflues :

- `GET /api/themes` : thèmes actifs triés par `ordre` (`ref`, `libelle`, `libelle_court`, `resume`, `ordre`, médias actifs), avec leurs sous-thèmes actifs en version légère (`ref`, `libelle`, `resume`, `ordre`, `histoire_ref` : première histoire publiée ou `null`). Ni article ni contacts.
- `GET /api/sous-themes/{ref}` : un sous-thème actif, `404` sinon. `ref`, `libelle`, `article`, `intro_ressources`, thème parent (`ref`, `libelle_court`), contacts actifs triés par `ordre` avec leurs téléphones actifs, `documents` (médias actifs de type `document`, soit les fiches PDF) et histoires publiées (`ref`, `titre`). Pas de `resume`.
- `GET /api/histoires/{ref}` : le graphe complet d'une histoire, en une seule requête. Le front passe ensuite de scène en scène sans nouvel appel.

```json
{ "data": {
    "ref": "demo_soiree_qui_derape",
    "titre": "Une soirée qui dérape (démo)",
    "scene_initiale_id": 18,
    "scenes": [
      { "id": 18,
        "dialogue_text": "…",
        "media": { "libelle": "…", "description": null, "url": "…", "type": "image" },
        "choix": [
          { "id": 10, "text_choix": "…", "next_scene_id": 19, "issue": null },
          { "id": 13, "text_choix": "…", "next_scene_id": null, "issue": "defavorable",
            "contacts": [ { "ref": "…", "nom": "…", "telephones": [ … ] } ] }
        ] }
    ] } }
```

- **Publication** : seules les histoires à l'état `publie` sont servies, les autres répondent `404`.
- **Fin de parcours** : `next_scene_id = null` sort du parcours, qualifié par `issue` (`favorable` ou `defavorable`).
- **Contacts** : présents seulement sur un choix `defavorable`, déjà résolus par le back (voir [Modèle de données](#modèle-de-données)). Le front n'a rien à calculer.
- **Ordre** : scènes triées par l'ordre de la liaison puis par `id`, choix par `id`.
- **Non exposés** : titre de scène (back-office), état, auteur, dates. `media` vaut `null` sans image active.
- **Intégrité** : un choix qui vise une scène non rattachée à l'histoire est écarté de la réponse. `scene_initiale_id` vaut `null` sans scène initiale.

---

## 4. Matrice des droits

Une ressource refusée disparaît du menu du back-office. Appelée directement, elle répond « accès interdit ».

### 4.1 Contenus

| Ressource | Webmaster | Rédacteur | Relecteur | Administrateur |
|---|---|---|---|---|
| Thèmes | Créer, lire, modifier, supprimer | Aucun droit | Aucun droit | Aucun droit |
| Sous-thèmes (fiches) | Créer, lire, modifier, supprimer | Aucun droit | Aucun droit | Aucun droit |
| Contacts | Créer, lire, modifier, supprimer | Aucun droit | Aucun droit | Aucun droit |
| Téléphones | Créer, lire, modifier, supprimer | Aucun droit | Aucun droit | Aucun droit |
| Médias | Créer, lire, modifier, supprimer | Créer, lire, modifier, supprimer | Aucun droit | Aucun droit |
| Histoires | Aucun droit | Créer, lire, modifier et supprimer les siennes en brouillon | Lire, modifier et supprimer toutes ; ne crée pas d'histoire | Aucun droit |
| Scènes | Aucun droit | Créer, lire, modifier et supprimer les siennes en brouillon | Lire, modifier et supprimer toutes | Aucun droit |
| Choix | Aucun droit | Créer, lire, modifier et supprimer les siennes en brouillon | Lire, modifier et supprimer toutes | Aucun droit |
| Prévisualiser une histoire | Non | Oui (les siennes) | Oui | Non |
| Changer l'état d'une histoire, jusqu'à « publié » | Non | Non (brouillon → relecture seulement) | Oui | Non |

Le circuit brouillon → relecture → validé → publié est contrôlé : le rédacteur ne peut que soumettre en relecture ; le relecteur valide, publie ou renvoie en brouillon.

### 4.2 Comptes du personnel

| Action | Webmaster | Rédacteur | Relecteur | Administrateur |
|---|---|---|---|---|
| Lister les comptes | Non | Non | Non | Oui |
| Créer un compte | Non | Non | Non | Non ³ |
| Attribuer ou retirer un rôle | Non | Non | Non | Oui ⁴ |
| Modifier les coordonnées d'un compte | Non | Non | Non | Oui ⁴ |
| Refuser une demande ou supprimer un compte | Non | Non | Non | Oui ⁴ ⁵ |
| Modifier ou supprimer son propre compte | Non | Non | Non | Non |
| Voir, modifier ou supprimer le compte visiteur | Non | Non | Non | Non |

³ Aucun compte n'est créé à la main : tout passe par l'inscription, puis la validation.
⁴ Rôles attribuables : webmaster, rédacteur, relecteur, administrateur. Le rôle visiteur ne s'attribue pas.
⁵ La suppression d'un rédacteur exige un repreneur pour ses histoires : un autre rédacteur ou un relecteur.

### 4.3 Cycle de vie d'un compte et de son rôle

```mermaid
flowchart LR
  I(["Inscription"]) --> S["Sans rôle<br/>connexion refusée"]
  S <-->|"rôle attribué ou retiré<br/>par un administrateur"| A["Actif<br/>connexion autorisée"]
  S --> F{"Fin du compte"}
  A --> F
  F -->|"refus ou suppression<br/>par un administrateur"| D(["Compte supprimé"])
  F -->|"3 ans sans connexion"| P(["Compte purgé"])
```

Un compte sans rôle, qu'il vienne de s'inscrire ou qu'on lui ait retiré son rôle, ne peut pas se connecter. Seul un administrateur fait changer un compte d'état. Il ne peut jamais agir sur son propre compte ni sur le compte visiteur, qui reste hors de ce cycle.

Garde-fou : un administrateur ne pouvant agir sur son propre compte, le dernier administrateur ne peut pas disparaître.

Purge automatique : une tâche quotidienne supprime les comptes sans connexion depuis 3 ans. Un compte jamais connecté est jugé sur sa date d'inscription. Sont exclus le compte visiteur, les administrateurs et les auteurs d'histoires : ces derniers sont signalés au journal d'audit, et un administrateur choisit le repreneur avant de les supprimer.

---

## 5. Exigences non fonctionnelles

### 5.1 Performance

| Exigence | Vérification | État |
|---|---|---|
| Premier affichage en moins de 3 secondes sur réseau 4G, grâce au rendu côté serveur. | Mesure Lighthouse mobile | À mesurer |
| Une seule requête API par page. Une histoire est chargée en une requête. | Revue des appels réseau | Conforme |

### 5.2 Ergonomie

| Exigence | Vérification | État |
|---|---|---|
| Conception mobile-first. Aucun défilement horizontal du mobile au poste de bureau. | Hors parcours | Conforme |
| Un numéro d'aide joignable en trois interactions au plus depuis l'accueil. | Parcours 1 | Conforme |
| Back-office utilisable sans compétence technique : guides, champs obligatoires, valeurs contraintes, références générées automatiquement. | Retours des testeurs | Conforme |

### 5.3 Sécurité

| Exigence | Vérification | État |
|---|---|---|
| Accès au back-office refusé à tout compte sans rôle et au compte visiteur. | Parcours 3 | Conforme |
| Limitation des tentatives : 5 connexions et 2 inscriptions par minute au back-office. | Essais répétés | Conforme |
| Mots de passe du back-office : 14 à 72 caractères, majuscule, minuscule, caractère spécial, refus des mots de passe ayant fuité. Stockage haché. | Essai d'inscription | Conforme |
| Le message « en attente de validation » n'apparaît qu'après un mot de passe correct : il ne révèle pas si un email est inscrit. | Parcours 3 | Conforme |
| Cookies sécurisés en production, en-têtes HTTP de sécurité. | Inspection des réponses HTTP | Partiel : back conforme (cookie de session `secure`, middleware `SecurityHeaders`) ; site étudiant avec en-têtes posés par `routeRules`, mais sans CSP de scripts, `x-powered-by` exposé et cookie `sc_token` sans `Secure` ni `HttpOnly` |
| Requêtes inter-domaines limitées au domaine du site étudiant. | Appel depuis un autre domaine | Conforme (`CORS_ALLOWED_ORIGINS`) |
| Téléversement des médias limité à une liste blanche de formats vérifiés sur le contenu (ni SVG ni HTML), 10 Mo par fichier. | Tests automatisés du back-office | Conforme |

### 5.4 Conformité RGPD

| Exigence | Vérification | État |
|---|---|---|
| Aucune donnée personnelle collectée auprès des étudiants. Aucun traceur d'audience. | Inventaire des cookies | Conforme |
| Les données d'un référent nommé de l'annuaire peuvent être effacées définitivement, en cascade. | Suppression d'un contact | Conforme |
| Les durées de conservation et les droits sont communiqués aux personnes concernées : page « Données personnelles » du site, CGU du back-office, résumé sur les pages de connexion et d'inscription. | Parcours 8 | À valider en recette. Contenu à faire valider par le DPO |
| Aucune ressource tierce non nécessaire chargée par le navigateur : polices hébergées sur le serveur. | Revue des requêtes réseau | Non conforme : polices chargées depuis Google |

### 5.5 Maintenabilité

| Exigence | Vérification | État |
|---|---|---|
| Lint, vérification de types et tests au vert avant chaque fusion. | Intégration continue ([README](../README.md#6-intégration-continue)) | Conforme : job `verif` sur chaque push et pull request, job `approfondi` avant `main`. Le front n'a pas de tests automatisés (build, typecheck, lint). |
| Environnement de développement entièrement conteneurisé, identique pour tous. | Démarrage par `docker compose up -d` | Conforme |

---

## 6. Contraintes

| Nature | Contrainte |
|---|---|
| Budgétaire | Aucun budget de licence : logiciels libres et services gratuits uniquement (OpenStreetMap, Nominatim, Heroicons). |
| Technique | Deux dépôts indépendants, déployables séparément : Safe-Campus-front et Safe-Campus-back. |
| Technique | Le site étudiant ne détient aucune donnée métier : tout transite par l'API. |
| Technique | Base relationnelle PostgreSQL. Intégrité garantie par contraintes : clés étrangères, unicité, contrôles de valeur. |
| Technique | Géocodage Nominatim limité à une requête par seconde. Acceptable vu le faible volume de saisie. |
| Réglementaire | RGPD : minimisation des données, droit à l'effacement. |
| Réglementaire | Données limitées à la Nouvelle-Calédonie. Les numéros métropolitains non joignables depuis le territoire sont exclus. |
| Humaine | Équipe étudiante réduite, calendrier de la SAE. Personnel de maintenance des contenus sans profil technique. |

---

## 7. Architecture technique

Architecture découplée : le back expose une API REST JSON et héberge le back-office. Le site étudiant est une application Nuxt rendue côté serveur, qui consomme l'API.

```mermaid
flowchart LR
  subgraph Front["Safe-Campus-front"]
    Nuxt["Nuxt 4, rendu serveur"]
  end
  subgraph Back["Safe-Campus-back"]
    API["API /api"]
    Admin["Back-office /admin"]
    DB[("PostgreSQL 17")]
  end
  Etudiant["Navigateur étudiant"] --> Nuxt
  Nuxt -->|"HTTP JSON"| API
  Personnel["Navigateur personnel"] --> Admin
  API --> DB
  Admin --> DB
  Admin -->|géocodage| Nominatim["Nominatim OpenStreetMap"]
```

| Couche | Technologie | Justification |
|---|---|---|
| Site étudiant | Nuxt 4, Vue 3, TypeScript | Rendu côté serveur pour un premier affichage rapide. Routage par fichiers. Typage. |
| Cartographie | Leaflet + OpenStreetMap | Libre, sans clé API, léger. Chargé uniquement sur la fiche contacts. |
| API | Laravel 13, PHP 8.4 | Framework mature : accès aux données, migrations, tests intégrés. |
| Back-office | Filament 3 | Écrans de gestion générés depuis les modèles. Aucun écran développé à la main. |
| Authentification API | Laravel Sanctum, jeton d'accès | Solution native Laravel, sans protocole de délégation externe. |
| Autorisations | Colonne de rôle contrainte + une règle d'autorisation par ressource | Rôles fixes, aucune dépendance supplémentaire. |
| Base de données | PostgreSQL 17 | Contrôles de valeur, index partiels. |
| Géocodage | Nominatim | Gratuit, sans clé. Adresse convertie en coordonnées à la saisie. |
| Conteneurisation | Docker, Docker Compose | Environnement identique pour tous, déploiement par image. |

### Modèle de données

Trois domaines indépendants, qui ne partagent que les médias, les comptes et la taxonomie. Les colonnes complètes sont dans `database/migrations/`.

| Domaine | Tables principales |
|---|---|
| Annuaire | `themes`, `sous_themes`, `contacts`, `telephones`, `medias`, `signalements` (module reporté) |
| Histoires | `histoires`, `scenes`, `choix` |
| Administration | `users` (avec rôle), `personal_access_tokens` (jetons) |

#### Annuaire

```mermaid
erDiagram
    Theme ||--o{ SousTheme : contient
    SousTheme }o--o{ Contact : "contact_sous_theme (ordre)"
    Contact ||--o{ Telephone : possede
    SousTheme }o--o{ Media : "media_sous_theme (ordre)"
    Theme }o--o{ Media : media_theme
    SousTheme ||--o{ Signalement : "si permet_signalement"
```

- **Le sous-thème est la fiche** : `sous_themes` porte le libellé, le résumé (carte d'accueil) et l'article (page détail). Ni fiche orpheline ni sous-thème sans fiche.
- **Ordre éditorial** : l'`ordre` de `contact_sous_theme` place les contacts universitaires en tête. Tout autre tri est traité par le front.
- **Contact** : `horaires` et `remarques` (public accueilli, conditions d'accès) en texte libre, `gratuit` et `anonyme` filtrables. Un contact sans sous-thème est invisible du front. `latitude` / `longitude` géocodées à la saisie ; `position_territoire` marque une structure qui couvre tout le territoire, sans coordonnées.
- **Téléphone** : `libelle` distingue plusieurs numéros d'une même structure ; `numero_vert` signale un numéro gratuit.
- **Actif et suppression** : `actif = false` retire un contenu du site sans casser les liaisons. La suppression définitive d'un contact cascade sur ses téléphones et ses rattachements (droit à l'effacement).
- **Signalement** : table conservée, interface reportée. Anonyme par construction (aucune clé vers `users`), ouvert sous-thème par sous-thème par `permet_signalement`.

#### Histoires

```mermaid
erDiagram
    User ||--o{ Histoire : redige
    Histoire }o--o{ SousTheme : histoire_sous_theme
    Histoire }o--o{ Scene : "histoire_scene (est_initiale, ordre)"
    Scene ||--o{ Choix : propose
    Choix }o--o| Scene : "mene a (next_scene_id)"
    Scene }o--o| SousTheme : bifurcation
    Scene }o--o| Media : illustre
```

- **Graphe** : une histoire est un graphe de scènes ; chaque choix mène à une scène suivante, ou sort du parcours (`next_scene_id = null`) avec une `issue` favorable ou défavorable.
- **Scène partagée** : une scène peut appartenir à plusieurs histoires. `est_initiale` et `ordre` sont portés par la liaison ; un index unique partiel garantit une seule scène initiale par histoire. La liaison fait autorité : toute scène atteignable doit y être rattachée.
- **Résolution des contacts** : sur une sortie défavorable, contacts actifs des sous-thèmes de l'histoire, triés par `ordre`. Si la scène porte un sous-thème (bifurcation), il remplace ceux de l'histoire.
- **Suppression d'une histoire** : ses scènes exclusives et leurs choix partent avec elle ; une scène encore utilisée reste. Les scènes orphelines se suppriment avec `scenes:purger-orphelines`.
- **Titre de scène** : libellé de back-office, jamais affiché au joueur.

#### Comptes du personnel

- **Rôles** : une colonne `role` sur `users`, contrainte en base, et une policy par ressource. Rôles fixes : `webmaster`, `redacteur`, `relecteur`, `admin`, plus `visiteur` (compte technique du portillon, sans accès au back-office). Spatie Permission et Filament Shield ont été écartés : deux paquets et quatre tables pour des rôles qui ne changent pas.
- **Inscription** : compte créé sans rôle, voir le [cycle de vie](#43-cycle-de-vie-dun-compte-et-de-son-rôle). Le téléphone est stocké au format E.164 (`+687…`).
- **Suppression d'un rédacteur** : la clé `histoires.user_id` est en cascade en base. `SuppressionCompteService` impose donc un repreneur avant la suppression, sans quoi les histoires seraient effacées.
- **Premier administrateur** : créé au `migrate` à partir de `ADMIN_EMAIL` / `ADMIN_PASSWORD`.

#### Règles d'implémentation

- Chaque entité de référence porte une `ref` stable (URL, import), jamais affichée ni modifiée, distincte du libellé, qui reste modifiable.
- Unicité : `ref` des thèmes, sous-thèmes, contacts et histoires, `email` des comptes, clé composite de chaque pivot.
- PostgreSQL n'indexe pas la colonne d'une clé étrangère : index déclaré explicitement sur chacune.
- `Media` et `Choix` déclarent leur table (`medias`, `choix`) : Laravel infère `media` et `choixes`.
- Enums stockés sans accent ni majuscule (`app/Enums`), castés sur les modèles.
- Aucun lien entre les comptes du personnel et les structures d'aide publiées : deux tables sans clé commune.
- Données limitées à la Nouvelle-Calédonie. Exceptions à valider : le 3114 et `arretonslesviolences.gouv.fr`.

Points ouverts : partage d'une scène avec des choix différents selon l'histoire (exigerait une clé `histoire_id` sur `choix`), horaires structurés pour afficher « ouvert maintenant », contrainte liant `issue` et `next_scene_id`, circuit de traitement des signalements.

---

## 8. Exigences d'exploitation

Aucun hébergeur n'est retenu. Le livrable est un jeu d'images Docker de production, déployables sur tout hôte Docker ([guide de déploiement](../README.md#5-déploiement-en-production)). Les exigences qui dépendent de l'hébergement (HTTPS, sauvegarde, supervision, planificateur) seront arrêtées avec l'hébergeur retenu.

| Exigence | État |
|---|---|
| Images de production dédiées, sans outils de développement ni de débogage. Le site étudiant ne contient que son build. | Conforme |
| Base de données non exposée hors du réseau Docker interne. | Conforme |
| HTTPS assuré par un reverse proxy et un certificat fournis par l'hébergement. | À fournir par l'hébergeur |
| Volumes persistants pour les médias et les journaux. | Conforme : volumes `scback-storage-prod` et `scback-logs-prod` déclarés |
| Serveur d'application de production (PHP-FPM ou équivalent). | Non conforme : serveur de développement `artisan serve`, lancé par `docker/8.4/start-prod`. Une tâche de démarrage qui échoue (migrations, seeders) n'arrête pas le serveur : lire les journaux après chaque déploiement |
| Conteneurs exécutés avec un utilisateur non administrateur. | Conforme : back (`scback`) et site étudiant (`node`) |
| Images de production sans portillon visiteur (compte, page de connexion, middleware). | Non conforme : les images exigent encore le compte visiteur, à adapter au lancement officiel |
| Sauvegarde quotidienne de la base et des médias, stockée hors du serveur, restauration testée chaque trimestre. | À fournir par l'hébergeur |
| Planificateur Laravel (`schedule:run` chaque minute), nécessaire à la purge des comptes inactifs. | À fournir par l'hébergeur : aucun planificateur dans l'image |
| Supervision de la disponibilité et des erreurs, avec alertes. | À fournir par l'hébergeur |
| Dimensionnement : 2 processeurs virtuels, 2 Go de mémoire, 40 Go de disque pour 50 à 500 utilisateurs simultanés. Minimum : 1 processeur virtuel, 1 Go. | Estimation |

---

## 9. Contenus à intégrer

| Contenu | Responsable | Mode d'intégration |
|---|---|---|
| Taxonomie (3 thèmes, 9 sous-thèmes) | Équipe projet | Livrée avec l'application |
| Articles et résumés des fiches | Webmaster | Back-office |
| Annuaire des structures d'aide (source CSV vérifiée) | Équipe projet, puis webmaster | Import initial, puis back-office |
| Fiches réflexives PDF | Webmaster | Back-office (médias) |
| Illustrations et logos | Équipe projet | Back-office et application |
| Histoires interactives | Rédacteurs | Back-office |
| Mentions légales et conditions générales d'utilisation | Équipe projet, complétées par l'établissement | Pages du site et du back-office |

---

## 10. Planning

| Phase | Période | Contenu |
|---|---|---|
| Cadrage | Avril 2026 | Analyse du besoin, choix techniques, dépôts, environnement Docker |
| Conception | Mai – juillet 2026 | Modèle de données, contrats d'API, maquettes |
| Développement — annuaire | Juillet – août 2026 | Taxonomie, contacts, médias, back-office, API, pages accueil / ressources / contacts, carte |
| Développement — histoires | Septembre 2026 | Modèle, éditeur, prévisualisation, lecteur interactif |
| Sécurisation et comptes | Septembre 2026 | Accès visiteur, inscription validée, rôle administrateur, journal d'audit |
| Intégration | Septembre – octobre 2026 | Pages légales, refonte graphique, guides du back-office |
| Recette | Octobre 2026 | Campagne de tests, corrections, validation |
| Mise en ligne | À fixer avec l'établissement | Choix de l'hébergeur, déploiement, reprise des contenus, formation |

---

## 11. Recette

### 11.1 Parcours de recette

Chaque parcours se rejoue de bout en bout. Les exigences qu'il vérifie le citent dans leur colonne « Vérification » ; les contrôles hors parcours sont décrits en tête de la [section 3](#parcours-de-recette).

#### Parcours 1 — Étudiant : accès et recherche d'aide

```mermaid
flowchart LR
  A["Ouverture d'une page du site"] --> B{"Connecté ?"}
  B -->|Non| C["Page de connexion"]
  C --> D["Saisie du mot de passe partagé"]
  D --> E["Retour à la page demandée"]
  B -->|Oui| F["Accueil : thèmes en sections, sous-thèmes en cartes"]
  E --> F
  F --> G["Recherche « clope »"]
  G --> H["Carte Tabac proposée"]
  F --> I["Carte d'un sous-thème"]
  H --> I
  I --> J["Fiche contacts : structures actives triées"]
  I --> K["Fiche ressources : article"]
  J --> L["Appel d'un numéro en un geste"]
  J --> M["Carte des structures"]
  K --> N["Fiche PDF ouverte dans un nouvel onglet"]
```

#### Parcours 2 — Étudiant : histoire interactive

```mermaid
flowchart LR
  A["Fiche du sous-thème"] --> B["Suivre l'histoire"]
  B --> C{"Histoire publiée ?"}
  C -->|Non| D["Page indisponible"]
  C -->|Oui| E["Scène"]
  E --> F["Choix"]
  F -->|Scène suivante| E
  F -->|Fin favorable| G["Fin favorable"]
  F -->|Fin défavorable| H["Contacts d'aide du thème"]
  G --> I{"Recommencer ?"}
  H --> I
  I -->|Oui| E
  I -->|Non| A
```

#### Parcours 3 — Personnel : inscription et accès au back-office

```mermaid
flowchart LR
  A["Page d'inscription du back-office"] --> B["Prénom, nom, email, téléphone, mot de passe"]
  B --> C["Compte créé sans rôle"]
  C --> D["Connexion"]
  D --> E{"Mot de passe correct ?"}
  E -->|Non| F["Identifiants invalides"]
  E -->|Oui| G{"Rôle attribué ?"}
  G -->|Non| H["« En attente de validation »"]
  H -.-> L["Un administrateur attribue un rôle"]
  L -.-> D
  G -->|Oui| I["Tableau de bord du rôle"]
  I --> J["Un rédacteur ouvre la gestion des contacts"]
  J --> K["Accès interdit, entrée absente du menu"]
```

#### Parcours 4 — Webmaster : mise à jour de l'annuaire

```mermaid
flowchart LR
  A["Gestion des contacts"] --> B["Création ou modification d'un contact"]
  B --> C["Saisie de l'adresse"]
  C --> D["Point placé sur la carte"]
  D --> E["Ajustement du point"]
  E --> F["Enregistrement"]
  F --> G["Entrée au journal d'audit : auteur, adresse IP, avant et après, sans secret"]
  F --> H{"Contact actif ?"}
  H -->|Non| I["Disparaît immédiatement du site étudiant"]
  H -->|Oui| J["Visible sur les fiches des sous-thèmes rattachés"]
```

#### Parcours 5 — Rédacteur : écriture et soumission d'une histoire

```mermaid
flowchart LR
  A["Gestion des histoires"] --> B["Création : titre, sous-thèmes"]
  B --> C["Écriture des scènes et des choix"]
  C --> D["Prévisualisation dans le back-office, sans publication"]
  D --> E{"Histoire prête ?"}
  E -->|Non| C
  E -->|Oui| F["Passage en relecture : l'histoire est verrouillée"]
  F --> G["Un relecteur la relit, la valide et la publie, ou la renvoie en brouillon"]
  G --> H["Histoire publiée : accessible depuis le site étudiant"]
```

#### Parcours 6 — Administrateur : suppression d'un compte

```mermaid
flowchart LR
  A["Gestion des comptes"] --> B{"Demande en attente ?"}
  B -->|Oui| C["Refus de la demande"]
  B -->|Non| D["Suppression du compte"]
  D --> E{"Rédacteur auteur d'histoires ?"}
  E -->|Oui| F["Choix obligatoire d'un repreneur"]
  F --> G["Histoires transférées, compte supprimé"]
  E -->|Non| H["Compte supprimé"]
```

#### Parcours 7 — Conservation des comptes : demande d'effacement et purge à 3 ans

```mermaid
flowchart LR
  A["Un membre du personnel demande la suppression de son compte"] --> B["Administrateur : Utilisateurs > Supprimer"]
  B --> C["Compte supprimé, entrée au journal d'audit"]
  C --> D["Connexion impossible avec l'ancien compte"]
  E["Compte sans connexion depuis plus de 3 ans"] --> F["Tâche quotidienne : comptes:purger-inactifs"]
  F --> G{"Auteur d'histoires, administrateur ou visiteur ?"}
  G -->|Non| H["Compte supprimé, User.purge_inactivite au journal"]
  G -->|Oui| I["Compte conservé, User.purge_ignoree au journal"]
  I --> B
```

#### Parcours 8 — Information sur les données personnelles

```mermaid
flowchart LR
  A["Site étudiant : pied de page ou menu"] --> B["Page Données personnelles"]
  B --> C["Durées, cookie du prototype, services tiers, droits, contact du DPO"]
  D["Back-office : page d'inscription"] --> E["Résumé des durées dont les 3 ans d'inactivité"]
  E --> F["Lien vers les CGU, section Données personnelles"]
  G["Back-office : page de connexion"] --> H["Mention du cookie de session et du journal"]
  H --> F
```

### 11.2 Critères de validation

- Tests automatisés, lint et vérification de types au vert sur les deux dépôts.
- Les huit parcours et les contrôles hors parcours validés sur Chrome, Firefox et Safari mobile.
- Aucune anomalie bloquante ou majeure ouverte.
- Toutes les exigences d'exploitation conformes avant la mise en production.

---

## 12. Mise en ligne et formation

1. Choisir l'hébergement (hôte Docker, reverse proxy, certificat) et signer le contrat de sous-traitance.
2. Générer une seule fois la clé de chiffrement de l'application et saisir les variables d'environnement.
3. Créer les volumes persistants et la sauvegarde avant le premier déploiement.
4. Déployer le back, puis lancer les migrations. Aucune donnée de démonstration en production.
5. Déployer le site étudiant, pointé sur l'adresse publique HTTPS de l'API.
6. Importer l'annuaire, vérifier les contenus, compléter les mentions légales, retirer le filigrane « prototype ».
7. Rejouer la recette sur l'environnement de production.

Formation : guides intégrés au back-office, puis une session de prise en main par rôle (webmaster, rédacteur, relecteur, administrateur).

---

## 13. Points à arrêter avec l'établissement

1. Hébergeur et offre retenus (hôte Docker, reverse proxy, sauvegardes).
2. Porteur de la maintenance après la SAE et temps alloué.
3. Date de mise en ligne.
