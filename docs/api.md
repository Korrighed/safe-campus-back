# API — Safe Campus

Contrats des routes de `routes/api.php`, consommées par [Safe-Campus-front](../../Safe-Campus-front). Le modèle de données qui les alimente est décrit dans [schema_bd.md](schema_bd.md). La forme JSON est définie par les classes de `app/Http/Resources/`.

## Routes

| Route | Authentification | Rôle |
|---|---|---|
| `POST /api/login` | Identifiants du compte visiteur | Portillon visiteur du prototype : renvoie un jeton Sanctum. Limité à 30 tentatives par minute et par IP. Retiré au lancement officiel ([securite-rgpd.md § 4.1](securite-rgpd.md#41-portillon-visiteur-du-prototype)). |
| `GET /api/themes` | Aucune | Accueil |
| `GET /api/sous-themes/{ref}` | Aucune | Fiche d'un sous-thème |
| `GET /api/histoires/{ref}` | Aucune | Graphe d'une histoire |

Les routes de lecture sont publiques par conception. Les modèles sont liés par `ref`, jamais par identifiant numérique.

## Principe

Trois endpoints de lecture, trois payloads distincts, pas de sur-fetch :

- `GET /api/themes` — `Theme` actifs, triés par `ordre` (`ref`, `libelle`, `libelle_court`, `resume`, `ordre`, `medias` actifs) avec leurs `SousTheme` actifs imbriqués en version légère (`ref`, `libelle`, `resume`, `ordre`, `histoire_ref` : première histoire publiée ou `null` — pas d'`article`, pas de `contacts`). Alimente les sections et les cartes de la page d'accueil.
- `GET /api/sous-themes/{ref}` — un seul sous-thème actif (`404` sinon) : `ref`, `libelle`, `article`, `intro_ressources`, `theme` (`ref`, `libelle_court`), `contacts` (triés `Ordre`, filtrés `Actif`) avec leurs `Telephone` actifs, `documents` (médias actifs de type `document`, soit les fiches PDF) et `histoires` (`ref`, `titre` des seules histoires `publie`, triées par titre : de quoi activer le bouton « Suivre l'histoire »). Un contact porte `ref`, `nom`, `prenom`, `mail`, `localisation`, `site_web`, `horaires`, `remarques`, `gratuit`, `anonyme`, `latitude`, `longitude`. Alimente la page détail. Pas de `resume` : inutilisé à cet endroit.
- `GET /api/histoires/{ref}` — le graphe complet d'une histoire, lu en **une seule requête** : le front navigue ensuite de scène en scène sans nouvel appel. Alimente le lecteur d'histoire.

`Article` n'est donc jamais renvoyé par `/api/themes`, `Resume` jamais par `/api/sous-themes/{ref}`.

## Contrat de `GET /api/histoires/{ref}`

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

- **Publication.** Seules les histoires `Etat = publie` sont servies, les autres répondent `404`.
- **Fin de parcours.** `next_scene_id = null` sort du parcours, qualifié par `issue` (`favorable` ou `defavorable`).
- **Contacts.** La clé `contacts` n'existe que sur un choix `defavorable`. Elle porte les contacts déjà résolus par la règle de [Résolution des contacts](schema_bd.md#choix) (contacts `Actif` et téléphones `Actif`) : le front n'a rien à calculer et ne refait aucun appel. Le bloc est répété d'un choix défavorable à l'autre, volontairement : les histoires sont petites.
- **Ordre.** Les scènes sont servies triées par `Ordre` de la liaison, puis par `id`. Les choix le sont par `id`. `Ordre` lui-même ne sort pas.
- **Ce qui ne sort pas.** `Scene.Titre` (back-office), `Etat`, l'auteur, les dates.
- **Média.** `media` vaut `null` sans image, ou si le média est inactif ou n'est pas une image.
- **Intégrité.** La liaison fait autorité : un choix dont `FK_Next_Scene` désigne une scène non rattachée à l'histoire est écarté de la réponse plutôt que de renvoyer un `next_scene_id` introuvable. `scene_initiale_id` vaut `null` si l'histoire n'a pas de scène initiale.
