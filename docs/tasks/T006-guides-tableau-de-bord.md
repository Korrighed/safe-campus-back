# T006 — Guides du tableau de bord

Statut : à faire
Story : US-03, US-04 · Exigences : F-10
Dépend de : T001
Parallélisable : oui
Exécutant : back

## Objectif

Le relecteur a son « Guide rapide — Relecteur » sur le tableau de bord. Le guide du rédacteur décrit le nouveau circuit : il soumet, il ne publie pas.

## Fichiers à lire

- `app/Filament/Widgets/GuideRedacteurWidget.php` — modèle du nouveau widget.
- `resources/views/filament/widgets/guide-redacteur.blade.php` — structure des sections, ton, classes.
- `resources/views/filament/widgets/guide-webmaster.blade.php` — comparer le ton.
- Rechercher les tests existants : `grep -rn "Guide" tests`.

## Fichiers cibles

| Fichier | Action | Ce qui change |
|---|---|---|
| `app/Filament/Widgets/GuideRelecteurWidget.php` | créer | Widget visible du relecteur seul. |
| `resources/views/filament/widgets/guide-relecteur.blade.php` | créer | Contenu du guide. |
| `resources/views/filament/widgets/guide-redacteur.blade.php` | modifier | Deux puces réécrites. |
| `tests/Feature/Filament/GuideWidgetsTest.php` | créer | Visibilité par rôle. |

## Étapes

1. Créer `GuideRelecteurWidget` en copiant `GuideRedacteurWidget` : `$view = 'filament.widgets.guide-relecteur'`, `canView()` vrai si `auth()->user()?->role === UserRole::Relecteur`, même `$sort`, `$isLazy`, `$columnSpan`.
2. Créer `guide-relecteur.blade.php` avec la même structure que `guide-redacteur.blade.php` (section englobante repliée, titre `Guide rapide — Relecteur`, sous-sections `compact collapsible collapsed persist-collapsed` avec des `id` uniques `guide-relecteur-…`). Phrase d'ouverture : « Tu es relecteur. Tu relis les histoires écrites par les rédacteurs, tu les corriges, puis tu les publies ou tu les renvoies en brouillon. » Trois sous-sections :
   - **Relire** (icône `heroicon-o-book-open`) : tu vois toutes les histoires, avec leur auteur. Filtre la liste par état pour retrouver celles en <em>Relecture</em>. Le menu de l'histoire a trois pages : Infos, Scènes, Prévisualiser. La page <em>Vérifier</em> liste ce qui manque.
   - **Corriger** (icône `heroicon-o-film`) : tu modifies une histoire dans n'importe quel état. Attention : corriger une histoire <em>Publiée</em> change immédiatement le site. Tu ne crées pas d'histoire.
   - **Faire avancer** (icône `heroicon-o-arrows-right-left`) : le champ <em>État</em> te laisse choisir librement : <em>Brouillon</em>, <em>Relecture</em>, <em>Validé</em> (approuvé), <em>Publié</em> (visible sur le site). Renvoie une histoire en <em>Brouillon</em> pour que son auteur la reprenne, ou pour la retirer du site. Rien n'est supprimé.
   Terminer par un lien `<x-filament::link :href="HistoireResource::getUrl()" ...>Ouvrir la page Histoires</x-filament::link>` comme dans le guide rédacteur (même `@php use`).
3. Dans `guide-redacteur.blade.php`, réécrire exactement deux puces de la sous-section « Histoires » :
   - la puce « Le champ <em>État</em> suit le circuit éditorial… » devient : `Le champ <em>État</em> : passe l'histoire de <em>Brouillon</em> à <em>Relecture</em> quand elle est prête. Elle est alors verrouillée : un relecteur la relit, la corrige, la publie ou te la renvoie en <em>Brouillon</em>. Seule une histoire <em>Publiée</em> apparaît sur le site.` ;
   - la puce « Pour retirer une histoire du site… » devient : `Tu ne vois que tes propres histoires. Pour modifier une histoire déjà soumise, demande à un relecteur de la renvoyer en <em>Brouillon</em>.`
4. Créer `tests/Feature/Filament/GuideWidgetsTest.php` (classe `Tests\Feature\Filament\GuideWidgetsTest`) :
   - `test_le_guide_relecteur_n_est_visible_que_du_relecteur` : pour chacun des rôles Webmaster, Redacteur, Relecteur, Admin, `actingAs` puis `GuideRelecteurWidget::canView()` n'est vrai que pour Relecteur.
   - `test_le_guide_redacteur_n_est_visible_que_du_redacteur` : même principe avec `GuideRedacteurWidget`.
   - `test_le_guide_relecteur_s_affiche` : relecteur connecté, `Livewire::test(GuideRelecteurWidget::class)->assertSee('Guide rapide — Relecteur')`.
5. Lancer la vérification.

## Critères d'acceptation

- [ ] AC-01 : Étant donné un relecteur, quand il ouvre le tableau de bord, alors il voit « Guide rapide — Relecteur » et pas le guide rédacteur.
- [ ] AC-02 : Étant donné un rédacteur, alors il ne voit que le guide rédacteur, qui ne mentionne plus la publication autonome.
- [ ] AC-03 : Les tests passent.

## Vérification

```bash
docker compose exec sc_back vendor/bin/pint --test
docker compose exec sc_back vendor/bin/phpstan analyse
docker compose exec sc_back php artisan test --filter=GuideWidgetsTest
```

Parcours navigateur : tableau de bord en relecteur puis en rédacteur : guide affiché, section déplie sans erreur.

## Hors périmètre

- Ne pas modifier le guide webmaster.
- Ne pas toucher aux autres puces du guide rédacteur.

## Notes d'exécution

- Créés : `app/Filament/Widgets/GuideRelecteurWidget.php` (copie de `GuideRedacteurWidget`, `canView()` sur `UserRole::Relecteur`), `resources/views/filament/widgets/guide-relecteur.blade.php` (section englobante, sous-sections Relire / Corriger / Faire avancer, lien « Ouvrir la page Histoires »), `tests/Feature/Filament/GuideWidgetsTest.php` (3 tests).
- Modifié : `resources/views/filament/widgets/guide-redacteur.blade.php` : deux puces de « Histoires » réécrites, rien d'autre.
- Écart volontaire à l'étape 2 : « La page <em>Vérifier</em> » écrit « L'entrée <em>Vérifier</em> du menu ». Raison : dans `HistoireResource::getNavigationItems()`, « Vérifier » est un `NavigationItem` (url `#verifier`) qui ouvre une modale (`OuvreVerification`, `actionVerifier()`), pas une page. Le guide rédacteur dit déjà « l'entrée Vérifier ». Retour au libellé littéral : une ligne dans `guide-relecteur.blade.php`.
- Vérifications non exécutées : consigne utilisateur (pint, phpstan, `artisan test` reportés au lot de fin). Relecture manuelle du diff : syntaxe PHP, imports triés, `new` sans parenthèses, directives Blade `@php`/`@endphp`.
- Point de vigilance hors périmètre : `HistoirePolicy::delete()` délègue à `update()`, donc le relecteur peut supprimer une histoire via l'action groupée « Supprimer la sélection » (`HistoireResource`, bulk action). La phrase « Rien n'est supprimé » du guide relecteur ne vaut que pour le renvoi en Brouillon. Texte imposé par la tâche : non modifié.
- AC-02 : la puce « … avant publication » (sous-section Vérifier et prévisualiser du guide rédacteur) est conservée, conformément à « Ne pas toucher aux autres puces ». Elle ne décrit pas une publication autonome.
