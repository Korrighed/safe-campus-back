# T011 — Limites de saisie visibles

Statut : à faire
Story : US-03 · Exigences : F-11, T-13
Dépend de : T010
Parallélisable : non
Exécutant : back

## Objectif

Chaque champ texte des formulaires d'histoire, de scène et de choix a une limite et l'écrit à l'écran (« N caractères maximum »). Le dialogue d'une scène, jusqu'ici illimité, reçoit une limite.

## Fichiers à lire

- `app/Filament/Resources/HistoireResource.php` — `champTitre()`.
- `app/Filament/Resources/HistoireResource/Pages/CreateHistoire.php` — assistant de création.
- `app/Filament/Resources/HistoireResource/Pages/ScenesHistoire.php` — `form()`.
- `database/migrations/2026_09_06_090001_create_scenes_table.php` et `..._090002_create_choix_table.php` — types de colonnes (`string` = 255, `text` = illimité).
- `tests/Feature/Filament/HistoireResourceTest.php`, `HistoireScenesTest.php`.

## Fichiers cibles

| Fichier | Action | Ce qui change |
|---|---|---|
| `app/Filament/Resources/HistoireResource.php` | modifier | Constantes de limites, `mentionLimite()`, `champTitre()`. |
| `app/Filament/Resources/HistoireResource/Pages/CreateHistoire.php` | modifier | Limites et mentions de l'assistant. |
| `app/Filament/Resources/HistoireResource/Pages/ScenesHistoire.php` | modifier | Limites et mentions du formulaire de scène. |
| `tests/Feature/Filament/HistoireResourceTest.php`, `HistoireScenesTest.php` | modifier | Validation de la limite. |

## Étapes

1. Mesurer d'abord les textes existants. Dans le conteneur : `docker compose exec sc_back php artisan tinker --execute="echo \App\Models\Scene::query()->selectRaw('max(length(dialogue_text)) as m')->value('m');"`. Si le résultat dépasse `DIALOGUE_MAX`, ARRÊTER et renvoyer une escalade avec la valeur mesurée : ne rien écrire.
2. Dans `HistoireResource.php`, ajouter les constantes publiques :

```php
    public const TITRE_MAX = 255;

    public const DIALOGUE_MAX = 1000;

    public const TEXTE_CHOIX_MAX = 255;

    public static function mentionLimite(int $max): string
    {
        return "{$max} caractères maximum.";
    }
```

3. `champTitre()` : `->maxLength(self::TITRE_MAX)` et `->helperText(self::mentionLimite(self::TITRE_MAX))`. Dans `CreateHistoire`, le champ appelle déjà `->helperText('Visible par les joueurs…')` : le remplacer par `->helperText('Visible par les joueurs une fois l\'histoire publiée. '.HistoireResource::mentionLimite(HistoireResource::TITRE_MAX))`.
4. `CreateHistoire` : `premiere_scene.titre` : `->maxLength(HistoireResource::TITRE_MAX)` (déjà 255) et texte d'aide `'Repère pour toi, jamais montré au joueur. '.HistoireResource::mentionLimite(HistoireResource::TITRE_MAX)`. `premiere_scene.dialogue_text` : `->maxLength(HistoireResource::DIALOGUE_MAX)` et `'Le texte que lit le joueur… depuis la page « 2. Scènes ». '.HistoireResource::mentionLimite(HistoireResource::DIALOGUE_MAX)` (conserver la phrase existante).
5. `ScenesHistoire::form()` : même traitement pour `titre` (255), `dialogue_text` (`maxLength(HistoireResource::DIALOGUE_MAX)`, aide existante + mention) et `text_choix` (255). Pour `text_choix`, qui n'a pas d'aide, ajouter `->helperText(HistoireResource::mentionLimite(HistoireResource::TEXTE_CHOIX_MAX))` ; si la mise en page du répéteur (champ sans libellé) rend l'aide gênante, utiliser plutôt `->placeholder('Ce que le joueur peut répondre (255 caractères maximum)')` et le noter.
6. Tests :
   - `HistoireResourceTest` : `test_le_dialogue_de_la_premiere_scene_est_limite` : `fillForm` avec un dialogue de `DIALOGUE_MAX + 1` caractères (`str_repeat('a', …)`) → `assertHasFormErrors(['premiere_scene.dialogue_text' => 'max'])`. Variante avec exactement `DIALOGUE_MAX` : pas d'erreur.
   - `HistoireScenesTest` : même vérification sur `donnees.dialogue_text` via `enregistrer`.
   - `test_les_formulaires_affichent_la_limite` : `Livewire::test(CreateHistoire::class)->assertSee('1000 caractères maximum.')` (utiliser la constante dans l'assertion).
7. Lancer la vérification.

## Critères d'acceptation

- [ ] AC-01 : Étant donné l'assistant de création, quand il s'affiche, alors chaque champ texte écrit sa limite.
- [ ] AC-02 : Étant donné un dialogue de `DIALOGUE_MAX + 1` caractères, alors l'enregistrement est refusé.
- [ ] AC-03 : Aucun texte existant n'est plus long que la limite (mesure du point 1).

## Vérification

```bash
docker compose exec sc_back vendor/bin/pint --test
docker compose exec sc_back vendor/bin/phpstan analyse
docker compose exec sc_back php artisan test --filter="HistoireResourceTest|HistoireScenesTest"
```

Parcours navigateur : créer une histoire, lire la limite sous chaque champ ; coller 1001 caractères dans le dialogue : refus.

## Hors périmètre

- Pas de migration de colonne.
- Pas de compteur dynamique (cette version de Filament n'en fournit pas).
- Pas d'autres formulaires que histoires, scènes et choix.

## Notes d'exécution

- Étape 1 : `max(length(dialogue_text))` = 233, sous `DIALOGUE_MAX` (1000). AC-03 satisfait pour les dialogues. Première tentative bloquée par `app/Filament/Widgets/GuideRelecteurWidget.php:23` (ligne `</content>`), corrigée par le coordinateur, relance OK.
- `text_choix` : aide `mentionLimite(TEXTE_CHOIX_MAX)` (défaut de l'étape 5). Placeholder inchangé. Mise en page du répéteur non vérifiée visuellement : repli possible sur le placeholder si l'aide gêne.
- Clés `assertHasFormErrors` relatives au statePath (`dialogue_text`, `premiere_scene.dialogue_text`) : Filament préfixe `donnees.` lui-même (`vendor/filament/forms/src/Testing/TestsForms.php:108-118`). La clé `donnees.dialogue_text` de la tâche aurait donné `donnees.donnees.dialogue_text`.
- Tests ajoutés : `HistoireResourceTest` (`test_le_dialogue_de_la_premiere_scene_est_limite`, `test_le_dialogue_de_la_premiere_scene_accepte_exactement_la_limite`, `test_les_formulaires_affichent_la_limite`) ; `HistoireScenesTest` (`test_le_dialogue_d_une_scene_est_limite`, `test_le_dialogue_d_une_scene_accepte_exactement_la_limite`).
- Non exécuté : pint, phpstan, php artisan test (consigne utilisateur). À lancer par le verifier.
