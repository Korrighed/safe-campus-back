# Historique des versions — Safe Campus back

Format inspiré de [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/). Versionnage [SemVer](https://semver.org/lang/fr/).

## [1.0.0] — 2026-10-30

Première version de clôture du projet.

### Ajouté

- Rôle relecteur et suppression groupée dans le back-office.
- Histoires de démonstration : anxiété, harcèlement et sous-thèmes restants.
- Purge des comptes inactifs.
- Intégration continue du back, déclenchement manuel.
- Préparation de la production : images Docker, démarrage sur Render, guide de déploiement dans le README.
- Documentation réduite à trois livrables : cahier des charges techniques, sécurité et RGPD, maintenance évolutive.

### Modifié

- Audit de sécurité final : S-05 traité côté front, état des dépendances au 30/10/2026.
- Documentation de déploiement : solution actuelle présentée comme temporaire.

### Corrigé

- CVE-2026-45070 sur `symfony/mime` (v8.1.1x).

## [0.2.0] — 2026-10-28

Version livrée via `release/0.2`.

### Ajouté

- Authentification et inscription visiteur, espace « Mon espace », compte administrateur.
- Journaux d'audit.
- Mentions légales.
- Guide rédacteur et rédacteur de démonstration.
- Fiches réflexives sur les addictions.
- Contacts : géolocalisation, sites web, carte intégrée, référence automatique.
- API des histoires.

### Sécurité

- Sécurisation du téléversement des médias.
- Mise à jour des dépendances.
