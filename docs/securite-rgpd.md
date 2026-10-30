# Documentation sécurité et RGPD — Safe Campus

| Élément | Valeur |
|---|---|
| Projet | Safe Campus — SAE501, Université de la Nouvelle-Calédonie |
| Date | 20/10/2026 |
| Périmètre | Site étudiant (Nuxt 4), API et back-office (Laravel 13 / Filament 3), base PostgreSQL 17, images Docker de production |
| État du code | Branche `dev` au 20/10/2026 |
| Hébergement | Aucun hébergeur retenu. Le livrable est un jeu d'images Docker de production. |

Le document comprend deux parties : la sécurité (sections 1 à 9), puis la protection des données personnelles (sections 10 à 16).

> Aucune solution d'hébergement n'est choisie. Les mesures qui dépendent de l'hébergement (TLS, reverse proxy, volumes, sauvegarde, supervision) sont des préconisations : voir § 8.1. Elles seront arrêtées avec l'hébergeur retenu.

---

## 1. Objet et périmètre

Ce document décrit les mesures de sécurité de Safe Campus et la manière dont l'application traite les données personnelles. Il sert de référence à l'équipe projet, à l'établissement qui exploitera la plateforme et à son délégué à la protection des données (DPO).

Il couvre les deux applications du projet :

- **Safe-Campus-front** : site consulté par les étudiants.
- **Safe-Campus-back** : API REST, back-office d'administration (`/admin`) et base de données.

### 1.1 Principes directeurs

- **Protection dès la conception** : l'étudiant n'a ni compte, ni formulaire, ni suivi d'audience. Il n'y a donc aucune donnée sur lui à protéger.
- **Moindre privilège** : chaque membre du personnel n'accède qu'aux sections nécessaires à son rôle.
- **Défense en profondeur** : contrôles au niveau de l'interface, de l'application (Policies) et de la base (contraintes).
- **Traçabilité** : toute action du back-office est journalisée, sans jamais enregistrer de secret.

### 1.2 Cadre réglementaire

Le projet prend comme référentiel le RGPD (règlement UE 2016/679) et la loi Informatique et Libertés. La plateforme est exploitée en Nouvelle-Calédonie, où les règles applicables aux données personnelles présentent des spécificités.

> Le régime juridique exact applicable en Nouvelle-Calédonie doit être confirmé par le DPO de l'Université de la Nouvelle-Calédonie avant la mise en production.

---

## 2. Architecture et surfaces d'exposition

| Composant | Exposition | Protection |
|---|---|---|
| Site étudiant (Nuxt, SSR) | Public, HTTPS | Public par conception. Pendant les événements du prototype : portillon visiteur (§ 4.1). |
| API REST (`/api`) | Public, HTTPS | Lecture seule de contenus publiés, publique par conception. Seule route en écriture : `POST /api/login` (portillon visiteur). |
| Back-office (`/admin`) | Public, HTTPS | Authentification individuelle, rôles, limitation des tentatives |
| Base PostgreSQL | Réseau Docker interne uniquement | Aucun port publié en production |
| Fichiers médias | Public (lien symbolique `storage`) | Ne contient que des contenus destinés à être publiés. Formats limités par une liste blanche, 10 Mo par fichier (§ 6.3). |
| Journaux (`laravel`, `audit`) | Serveur uniquement | Aucun écran de consultation, accès par le conteneur |

### 2.1 Services tiers appelés

| Service | Appelé depuis | Donnée transmise | Finalité |
|---|---|---|---|
| OpenStreetMap (tuiles) | Navigateur (site et back-office) | Adresse IP, zone de carte consultée | Affichage des cartes |
| Esri ArcGIS (tuiles satellite) | Navigateur (back-office) | Adresse IP, zone consultée | Vue satellite lors de la saisie d'une adresse |
| Nominatim (OSM) | Serveur back | Adresse d'une structure d'aide | Géocodage d'un contact |
| Have I Been Pwned | Serveur back | 5 premiers caractères du SHA-1 du mot de passe (k-anonymat) | Refus des mots de passe ayant fuité |
| Google Fonts | Navigateur (site) | Adresse IP, en-têtes HTTP | Chargement des polices Cabin et Bebas Neue |

---

## 3. Classification des données

| Donnée | Exemple | Sensibilité | Personnelle ? |
|---|---|---|---|
| Contenus éditoriaux | Articles, histoires, médias | Publique | Non |
| Annuaire — structures | Nom de l'association, standard, horaires | Publique | Non |
| Annuaire — référent nommé | Nom, prénom, email d'un intervenant | Publique, mais personnelle | Oui |
| Comptes du back-office | Nom, prénom, email, téléphone, rôle | Interne | Oui |
| Mots de passe | Hash bcrypt | Secret | Oui |
| Jetons d'accès | Jeton Sanctum du portillon visiteur | Interne | Non (compte partagé) |
| Journal d'audit | Auteur, action, IP, date | Interne | Oui |
| Secrets de configuration | `APP_KEY`, mots de passe BD et visiteur | Secret | Non |

> Aucune donnée de santé n'est traitée. L'application ne sait pas quel étudiant consulte quelle fiche : pas de compte individuel, pas de mesure d'audience, pas de formulaire.

---

## 4. Authentification

### 4.1 Portillon visiteur du prototype

Le site étudiant et l'API de lecture sont publics par conception. En production, ils sont consultables sans compte ni mot de passe. Seul le back-office (`/admin`) et ses ressources sont protégés.

Le prototype n'est pas déployé en production. Lors des événements où la plateforme est présentée, un **portillon visiteur** limite l'accès au site étudiant. Ce portillon n'est pas une authentification : il ne protège aucune donnée, l'API de lecture restant publique.

- Un middleware global redirige vers `/login` toutes les pages, sauf `/login`, `/mentions-legales`, `/cgu` et `/donnees-personnelles`, tant que le cookie `sc_token` est absent. Il vérifie la présence du cookie, pas sa validité.
- Le mot de passe est partagé. Le compte visiteur n'est pas nominatif. Ses identifiants sont définis dans la configuration serveur (`VISITEUR_EMAIL` / `VISITEUR_PASSWORD`), jamais dans le code. Le mot de passe est changé par variable d'environnement entre deux événements.
- `POST /api/login` renvoie un jeton Sanctum. Le front le stocke dans le cookie `sc_token` (`SameSite=Lax`).
- Le jeton n'a pas de durée de vie : le portillon n'a pas vocation à expirer pendant un événement.
- Le compte visiteur ne peut pas accéder au back-office : `canAccessPanel()` n'accepte que `webmaster`, `redacteur`, `relecteur` et `admin`.
- En production, le compte visiteur, la page de connexion et le middleware sont retirés.

### 4.2 Comptes du back-office

- Comptes individuels et nominatifs. La session Laravel (guard `web`) utilise un cookie `HttpOnly` et `SameSite=Lax`.
- **Inscription contrôlée** : un compte créé depuis `/admin/register` n'a aucun rôle. Il ne peut pas se connecter tant qu'un administrateur ne lui en a pas attribué un.
- **Anti-énumération** : le message « compte en attente de validation » ne s'affiche qu'après un mot de passe correct. Il ne révèle donc pas si une adresse email est inscrite.
- **Limitation des tentatives** : 5 connexions par minute et 2 inscriptions par minute.
- Email enregistré en minuscules, unique sans tenir compte de la casse.
- Premier administrateur créé à la migration (`ADMIN_EMAIL` / `ADMIN_PASSWORD`). En production, la migration échoue si son mot de passe ne respecte pas la politique ci-dessous.

### 4.3 Politique de mots de passe

| Règle | Valeur |
|---|---|
| Longueur | 14 à 72 caractères |
| Complexité | Au moins une majuscule, une minuscule et un caractère spécial |
| Fuites connues | Refusé s'il apparaît dans la base Have I Been Pwned. k-anonymat : le mot de passe ne quitte jamais le serveur. |
| Stockage | Hash bcrypt, jamais en clair, jamais journalisé |

---

## 5. Autorisations et rôles

Chaque compte a un rôle unique. La liste des rôles est fixée par une énumération et une contrainte CHECK en base : aucun écran ne permet d'en créer. Les droits sont appliqués par une Policy Laravel par ressource. Une ressource non autorisée disparaît du menu et répond 403 si on y accède directement.

| Ressource | `webmaster` | `redacteur` | `relecteur` | `admin` | `visiteur` |
|---|---|---|---|---|---|
| Thèmes / Sous-thèmes | ✓ | — | — | — | — |
| Contacts / Téléphones | ✓ | — | — | — | — |
| Médias | ✓ | ✓ | — | — | — |
| Histoires / Scènes / Choix | — | ✓ (les siennes, en brouillon) | ✓ (toutes ; pas de création d'histoire) | — | — |
| Utilisateurs | — | — | — | ✓ | — |
| Accès au back-office | ✓ | ✓ | ✓ | ✓ | ✗ |

### 5.1 Garde-fous

- Un administrateur ne peut ni modifier ni supprimer son propre compte. Le dernier administrateur ne peut donc pas disparaître.
- Le compte visiteur n'apparaît pas dans la liste des utilisateurs. On ne peut ni l'attribuer à quelqu'un ni le supprimer.
- Avant de supprimer un rédacteur, il faut transférer ses histoires à un autre rédacteur ou à un relecteur. Sans repreneur, rien n'est supprimé.
- Un rédacteur ne voit que ses histoires, et ne modifie ni ne supprime que celles en brouillon.
- Un rédacteur ne peut pas poser `valide` ni `publie` : la valeur est refusée côté serveur.
- Retirer son rôle à un compte le désactive immédiatement, sans le supprimer.

---

## 6. Protection des données

### 6.1 En transit

- HTTPS de bout en bout : le certificat TLS et le reverse proxy sont à fournir par l'hébergement retenu. Les conteneurs ne parlent que HTTP.
- Requêtes inter-domaines : `config/cors.php` limite l'API (`api/*`) aux origines de `CORS_ALLOWED_ORIGINS`, sans cookies cross-origin.

### 6.2 Au repos

- Base PostgreSQL accessible uniquement depuis le conteneur back. Mot de passe aléatoire d'au moins 20 caractères.
- L'application se connecte avec le compte créé par l'image PostgreSQL (`POSTGRES_USER`), qui est superutilisateur.
- `APP_KEY` générée une seule fois, conservée dans les variables d'environnement de l'hébergement, jamais régénérée.
- Les secrets sont stockés uniquement dans les variables d'environnement. Les fichiers `.env` sont exclus de Git et des images Docker.

### 6.3 Sécurité applicative

| Risque | Mesure |
|---|---|
| Injection SQL | ORM Eloquent et requêtes paramétrées, aucune concaténation SQL |
| XSS | Contenus affichés par l'interpolation Vue (échappement automatique). Aucun `v-html` dans le front (vérifié le 20/10/2026). |
| CSRF | Jeton CSRF natif de Laravel / Livewire sur le back-office. L'API utilise un jeton Bearer, pas de cookie de session. |
| Saisie forgée | Validation serveur de chaque champ (type, format, unicité), y compris quand un tableau est envoyé à la place d'une chaîne |
| Fuite d'informations | `APP_DEBUG=false` et `LOG_LEVEL=error` en production. L'API n'expose jamais les chemins disque, l'auteur, l'état ou les dates. |
| Fichier piégé au téléversement | Liste blanche de types MIME par type de média, vérifiés sur le contenu du fichier et non sur son extension : PNG, JPEG, WebP · MP4, WebM · MP3, OGG · PDF. SVG et HTML refusés : servis depuis le domaine du back-office, ils pourraient exécuter du script. 10 Mo par fichier, limite PHP à 12 Mo. Le type d'un média n'est plus modifiable après sa création. |
| Redirection ouverte | Le paramètre de retour du lecteur d'histoire n'accepte que des chemins internes (commence par `/`, jamais par `//`). Celui de la page de connexion est passé à `navigateTo()`, qui refuse toute URL externe par défaut. |
| Contenu non publié | Seules les histoires « publiées » et les contacts / médias actifs sont servis par l'API |

---

## 7. Traçabilité et journalisation

| Caractéristique | Valeur |
|---|---|
| Canal | `audit`, distinct du journal applicatif |
| Fichiers | `storage/logs/audit-AAAA-MM-JJ.log`, un par jour |
| Format | JSON, une entrée par ligne, horodatage UTC |
| Conservation | 365 jours, purge automatique |
| Événements | Création, modification (avant / après) et suppression des données métier. Connexion, échec de connexion, déconnexion, inscription. Transfert d'histoires. Purge d'un compte inactif (`User.purge_inactivite`) ou ignorée faute de repreneur (`User.purge_ignoree`), auteur `console`. Circuit d'une histoire : `Histoire.etat_change` (`de`, `vers`). Rattachements d'une histoire : `Histoire.scene_ajoutee` (`scene_id`, et `reutilisee` si la scène existait déjà), `Histoire.scene_retiree` (`scene_id`), `Histoire.scene_deplacee` (`scene_id`, `sens` haut ou bas), `Histoire.depart_defini` (`scene_id`), `Histoire.sous_themes_modifies` (`ajoutes`, `retires`). Accès refusés : `Histoire.acces_refuse` (`motif` : `hors_perimetre` pour un 404 ; `droit_refuse` pour un 403 de page ou d'écriture de scène, avec `capacite`). Suppression groupée d'histoires : `Histoire.suppression_groupee` (`supprimees`, `refusees` : identifiants seulement). |
| Contenu d'une entrée | Action, objet concerné, auteur (identifiant et rôle), adresse IP, détails |
| Exclusions | Jamais de mot de passe ni de jeton. Jamais le titre ni le texte d'une scène ou d'un choix : seuls l'identifiant et le nom des champs modifiés sont écrits. Autres textes longs tronqués à 200 caractères. Jamais d'adresse e-mail, de nom, de prénom ni de téléphone de compte : un compte est désigné par son identifiant. Échec de connexion (`auth.echec`) : jamais l'adresse saisie ; `compte_id` seulement si le compte existe, et `compte_connu`. |
| Fiabilité | Écrit après validation de la transaction : une action annulée n'est pas tracée |
| Accès | Serveur uniquement |

> Les entrées écrites avant cette règle peuvent contenir les 200 premiers caractères d'un dialogue, et des adresses e-mail (comptes et adresses saisies à la connexion), jusqu'à leur purge à 365 jours. Une adresse saisie par erreur peut même contenir un mot de passe.

> Limite connue : les e-mails et téléphones des contacts de l'annuaire peuvent encore apparaître dans les valeurs journalisées. À traiter séparément.

> Les rattachements des histoires (scènes, sous-thèmes) sont tracés par des événements explicites (`Histoire.scene_ajoutee`, `Histoire.sous_themes_modifies`, etc.). Les autres rattachements (média ↔ sous-thème, contact ↔ sous-thème) ne déclenchent pas d'événement de modèle : ils ne sont pas tracés.

> Les journaux sont montés sur le volume `scback-logs-prod` : un redéploiement ne les efface pas. Leur conservation hors serveur relève de l'hébergement (§ 8.1).

---

## 8. Infrastructure et déploiement

Aucun hébergeur de production n'est retenu. Le livrable s'arrête aux images Docker de production, déployables sur tout hôte Docker. Le reverse proxy, le certificat TLS, le stockage persistant et la sauvegarde sont à fournir par l'hébergement.

La procédure est décrite dans le [guide de déploiement](../README.md#5-déploiement-en-production).

- Les images de production sont séparées des images de développement. Elles ne contiennent ni Xdebug, ni Adminer, ni dépendances de développement. L'image front ne contient que le build `.output/`, sans code source.
- Les fichiers `.env` sont exclus des images (`.dockerignore`).
- Les conteneurs back et site étudiant tournent avec un utilisateur non administrateur. L'image de production du site étudiant utilise `node`, fourni par l'image Node, et ne peut pas modifier son build `.output/`.
- L'image back sert l'application avec `php artisan serve`, serveur de développement mono-processus.
- Le réseau Docker de production (`scback_prod`) est séparé de celui de développement (`scback`).
- Les commandes post-déploiement excluent tout jeu de données de démonstration : `db:seed` est interdit en production. `DEMO_CONTENT` (seeders de démonstration, faux par défaut) reste désactivé.
- Une sauvegarde de la base est obligatoire avant toute migration en production.
- Les médias (`storage/app`) et les journaux (`storage/logs`) sont montés sur les volumes nommés `scback-storage-prod` et `scback-logs-prod`. Reste la sauvegarde hors serveur.

### 8.1 Préconisations pour l'hébergement

Ces préconisations servent de cahier des charges pour le choix de l'hébergeur. Elles ne sont pas mises en œuvre.

| Domaine | Préconisation |
|---|---|
| Exposition | Seuls les ports 80 et 443 ouverts. Le port 80 redirige vers HTTPS. Base et conteneurs jamais exposés directement. |
| TLS | Reverse proxy devant les deux conteneurs, certificat renouvelé automatiquement. |
| En-têtes et débit | En-têtes de sécurité et limitation de débit posés au niveau du reverse proxy, en complément de l'application. |
| Stockage | Volumes persistants pour la base, les médias et les journaux. |
| Sauvegarde | Sauvegarde quotidienne de la base et des médias, stockée hors du serveur. Restauration testée chaque trimestre. |
| Secrets | Variables d'environnement gérées par l'hébergement, jamais dans un fichier versionné. Accès réservé aux administrateurs techniques. |
| Supervision | Disponibilité, erreurs applicatives, espace disque. Alertes sur les échecs de connexion répétés du journal d'audit. |
| Préproduction | Environnement identique à la production pour valider migrations et montées de version avant la mise en ligne. |
| Localisation | Serveurs situés dans une zone compatible avec le régime de protection des données retenu par le DPO (§ 1.2). |
| Contrat | Contrat de sous-traitance conforme à l'article 28 du RGPD (§ 15). |

---

## 9. Maintenance de la sécurité

| Action | Fréquence |
|---|---|
| Revue et mise à jour des dépendances npm / Composer (Dependabot ou Renovate) | Mensuelle |
| Veille CVE sur Nuxt, Laravel, Filament, PHP, Node, PostgreSQL | Continue |
| Revue des comptes du back-office et des rôles attribués | Trimestrielle |
| Changement du mot de passe du portillon visiteur | Entre deux événements du prototype, et dès qu'une fuite est suspectée |
| Rotation des secrets (BD, compte admin initial) | Annuelle, ou après le départ d'un administrateur |
| Test de restauration d'une sauvegarde | Trimestrielle |
| Montées de version majeures | Selon le calendrier de fin de support des composants |

État des dépendances au 30/10/2026 (audits rejoués par la CI, job `approfondi`, voir le [README](../README.md#6-intégration-continue)) :

| Dépôt | Outil | Vulnérabilités connues |
|---|---|---|
| Back | `composer audit --locked` | Aucune. Bloquant en CI. `symfony/mime` corrigé en v8.1.10 (CVE-2026-45070, injection d'en-tête email ; l'application n'envoie pas d'email). |
| Front | `npm audit` | 7 de gravité basse (`deepmerge`, prototype poisoning, via la chaîne `nuxt` / `nitropack`) ; aucune haute ni critique. `node-forge` et `serialize-javascript` corrigés dans le lockfile. Les critiques bloquent la CI ; les hautes sont affichées sans bloquer. Le seul correctif proposé par npm pour la chaîne `deepmerge` est un retour à `nuxt@4.1.3`, non retenu. |
| Images de prod | Trivy, CRITICAL corrigeables | Aucune. Bloquant en CI. |

Dependabot n'est pas configuré : la revue mensuelle ci-dessus reste manuelle.

---

## 10. Acteurs et responsabilités

| Rôle RGPD | Acteur |
|---|---|
| Responsable de traitement | Université de la Nouvelle-Calédonie (établissement exploitant) — à confirmer |
| Délégué à la protection des données | DPO de l'établissement — coordonnées à compléter |
| Sous-traitant hébergement | Hébergeur — à désigner |
| Conception | Équipe projet SAE501 |

---

## 11. Registre des traitements

| Traitement | Personnes concernées | Données | Finalité | Base légale | Conservation |
|---|---|---|---|---|---|
| Gestion des comptes du back-office | Personnel du campus | Nom, prénom, email, téléphone, rôle, mot de passe (hash) | Administrer les contenus | Mission d'intérêt public / intérêt légitime | Durée de la fonction. Suppression par l'admin, ou automatique après 3 ans sans connexion. |
| Demandes d'inscription | Personnel demandeur | Idem | Valider l'accès | Mesures précontractuelles / intérêt légitime | Jusqu'à validation ou refus |
| Annuaire des structures d'aide | Référents nommés des structures | Nom, prénom, email professionnel, adresse de permanence | Orienter les étudiants vers l'aide | Mission d'intérêt public | Tant que le référent est en poste. Effacement sur demande. |
| Journal d'audit | Utilisateurs du back-office | Identifiant du compte, rôle, action, IP, date | Sécurité, traçabilité | Intérêt légitime / obligation de sécurité | 365 jours |
| Session du back-office | Utilisateurs du back-office | Cookie de session, identifiant, IP, navigateur (table `sessions`) | Maintenir la connexion | Intérêt légitime | 120 minutes d'inactivité (`SESSION_LIFETIME`) |
| Services tiers du site (polices, cartes) | Visiteurs du site | IP et en-têtes HTTP transmis à Google Fonts et OpenStreetMap (§ 2.1) | Afficher polices et cartes | À confirmer par le DPO | Aucune conservation par Safe Campus |
| Portillon visiteur (prototype) | Visiteurs des événements | Aucune donnée identifiante (compte partagé). Cookie technique. | Restreindre l'accès au prototype pendant les événements | Intérêt légitime | Durée du cookie de session navigateur |

Les bases légales sont à valider par le DPO.

---

## 12. Principes appliqués

| Principe | Application dans Safe Campus |
|---|---|
| Minimisation | Aucun compte, formulaire ou outil de mesure d'audience pour les étudiants. Pour le personnel, seules les données utiles au contact et à l'administration sont collectées. |
| Limitation des finalités | Les données du personnel ne servent qu'à la gestion des accès. `User` et `Contact` sont deux tables sans aucun lien. |
| Exactitude | Contacts tenus à jour par le webmaster. Champ `actif` pour retirer immédiatement une structure du site. |
| Limitation de la conservation | Purge automatique de l'audit à 365 jours. Purge automatique des comptes sans connexion depuis 3 ans (`comptes:purger-inactifs`, quotidienne). Suppression des comptes et des contacts nommés possible à tout moment. |
| Intégrité et confidentialité | Mesures de la partie A (§ 4 à 9) |
| Protection dès la conception | Pas de collecte côté étudiant. La consultation de thématiques sensibles (santé mentale, violences) ne laisse aucune trace nominative. |

---

## 13. Droits des personnes

| Droit | Mise en œuvre |
|---|---|
| Accès / rectification | Sur demande, l'administrateur communique ou rectifie les données d'un compte. Le webmaster fait de même pour un contact. |
| Effacement | Suppression définitive d'un compte par l'admin, ou d'un contact par le webmaster. Suppression en cascade des téléphones et des rattachements. |
| Opposition / limitation | Retrait du rôle (compte conservé mais inactif) ou désactivation d'un contact |
| Portabilité | Extraction des données du compte sur demande, par requête en base |
| Réclamation | Auprès du DPO de l'établissement, puis de l'autorité de contrôle compétente |

**Procédure** : la demande est adressée au contact indiqué dans les mentions légales. Elle est transmise au DPO et traitée dans un délai d'un mois. L'opération réalisée dans le back-office apparaît dans le journal d'audit.

**Purge des comptes inactifs** : la date de dernière connexion (`users.derniere_connexion_at`) est mise à jour à chaque connexion au back-office. Un compte sans connexion depuis 3 ans (inscription pour un compte jamais connecté) est supprimé chaque jour. Exclus : le visiteur, les admins, et les comptes auteurs d'histoires (signalés dans le journal d'audit, `User.purge_ignoree`, un admin choisit le repreneur et supprime à la main). Aucun email d'avertissement n'est envoyé avant suppression.

> L'effacement d'un compte supprime la ligne en base. Les entrées déjà écrites dans le journal d'audit sont conservées jusqu'à leur purge automatique à 365 jours, au titre de l'obligation de sécurité.

---

## 14. Cookies et traceurs

| Cookie | Application | Rôle | Consentement |
|---|---|---|---|
| `sc_token` | Site étudiant (prototype uniquement) | Contient le jeton du portillon visiteur | Non requis (strictement nécessaire) |
| Session Laravel | Back-office | Maintient la connexion du personnel | Non requis (strictement nécessaire) |
| `XSRF-TOKEN` | Back-office | Protection contre le CSRF | Non requis (strictement nécessaire) |

Aucun cookie publicitaire ou de mesure d'audience n'est déposé : aucun bandeau de consentement n'est nécessaire.

### 14.1 Ressources tierces

Le chargement des polices Google Fonts et des tuiles cartographiques transmet l'adresse IP du visiteur à ces services. Google est situé hors de l'Union européenne : il est recommandé d'héberger les polices sur le serveur Safe Campus. Les tuiles OpenStreetMap ne sont chargées que sur les pages contact.

---

## 15. Sous-traitants et transferts

| Destinataire | Données | Localisation | Encadrement |
|---|---|---|---|
| Hébergeur | Toutes les données de la plateforme | À désigner | Contrat de sous-traitance (art. 28 RGPD) |
| GitHub (dépôts publics, CI) | Code source, historique, résultats de la CI | États-Unis | Dépôts publics : aucun secret détecté dans l'historique (scan du 27/10/2026). Les noms et adresses e-mail des auteurs de commits y sont visibles. |
| OpenStreetMap Foundation | IP du navigateur, adresses de structures | Royaume-Uni (décision d'adéquation) | Politique d'usage OSM |
| Esri | IP du navigateur (back-office) | États-Unis | Usage limité au personnel |
| Google Fonts | IP du navigateur | États-Unis | À supprimer |
| Have I Been Pwned | Fragment de hash non réversible | International | Pas de donnée personnelle |

---

## 16. Gestion des violations de données

```mermaid
flowchart LR
  D["Détection"] --> C["Confinement"]
  C --> E["Évaluation"]
  E --> N["Notification"]
  N --> Doc["Documentation"]
```

- **Détection** : alertes de supervision, anomalies dans le journal d'audit (échecs de connexion répétés, suppressions inhabituelles).
- **Confinement** : retrait du rôle des comptes compromis, révocation des jetons Sanctum, changement des secrets concernés (mot de passe visiteur, BD, `APP_KEY` si nécessaire).
- **Évaluation** : nature des données, nombre de personnes, gravité.
- **Notification** : au DPO sans délai. À l'autorité de contrôle dans les 72 heures si la violation présente un risque. Aux personnes concernées si ce risque est élevé.
- **Documentation** : inscription au registre des violations, retour d'expérience et mesures correctives.
