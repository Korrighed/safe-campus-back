# Retour des tests — Back

Filtré sur les retours back uniquement. Trié par temps de travail estimé, du plus court au plus long.

- [x] **[XS]** Infobulles / libellés clairs pour Thème et Sous-thème (à quoi ça correspond sur le site).
- [x] **[XS]** Rendre explicite que cliquer sur une ligne sous-thème dans le tableau ouvre la modification/création.
- [x] **[S]** Contraste des bordures : revoir les variables root Tailwind pour mieux faire ressortir les éléments d'UI.
- [x] **[S]** Visibilité du dépliant guide sur l'accueil : améliorer et remanier son emplacement/mise en avant.
- [x] **[S/M]** Rappel guide par section (petit encart d'aide contextuel par section du panel).
- [ ] **[S/M]** Guide : expliquer clairement comment rattacher un média / contact à un sous-thème.
- [x] **[M]** Compléter les sites internet pour les sites officiels et structures (Province Sud, etc.).
- [x] **[M]** Étoffer le guide avec plus d'éléments d'information.
- [ ] **[M/L]** Vérifier la gestion des langues FR/EN (comment fonctionnent les traductions) et permettre de choisir français/anglais.
- [ ] **[L]** À la saisie d'adresse en back, afficher un embed Leaflet pour visualiser le point sur la carte, avec possibilité d'ajuster légèrement la position depuis l'embed (et répercussion côté front).
- [ ] Split le guide rapide en plusieurs section pour mimic les menus et pas avoir un seul gros block a déplier

Légende : XS < 2h · S ≈ demi-journée · M ≈ 1 jour · L ≈ 2-3 jours (estimations à affiner).

## Fait (hors liste initiale, sorti des échanges de test)

- [x] Sous-thèmes affichés en un tableau distinct par thème (au lieu d'un tableau global filtré), avec création pré-remplie sur le bon thème.
- [x] Icône de « signalement » absent : trait gris au lieu du rond rouge à croix (absence ≠ interdiction).
- [x] Colonne « prénom » retirée du tableau contacts.
- [x] Menu latéral : suppression du sous-menu déroulant « Annuaire », items à plat.
- [x] Contraste mode clair : séparateurs de tableau / bordures de champs quasi invisibles (`gray-200`, ~1.3:1) retintés en bleu de marque directement dans la palette `gray` du panel (`AdminPanelProvider::colors()`).
- [x] Contours des sections, de la sidebar, de la topbar et des champs de formulaire (`ring-gray-950/5` et `/10`) renforcés en bleu de marque.
- [x] Menu latéral : l'item actif est désormais encadré d'une bordure bleue, en plus du fond grisé.
- [x] Chevron plier/déplier d'une section (guide webmaster, blocs de formulaire) agrandi et coloré en bleu pour repérer l'état replié/déplié au premier coup d'œil.
- [x] Entête de section lisible dès le premier coup d'œil (contraste renforcé + chevron bleu agrandi), qui sert désormais de rappel visuel systématique qu'un guide/encart d'aide est disponible par section.
- [x] Champ `ref` retiré du formulaire Contact : généré automatiquement depuis le nom (dédupliqué par suffixe si collision), un webmaster n'a plus à connaître cette notion.
- [x] Table `/contacts` : colonne `ref` retirée, colonnes gratuit/anonyme retirées (restent visibles en édition), colonne téléphones ajoutée.
- [x] Seeder : site web du contact SAMU retiré (`cht.nc` n'était pas le bon site).