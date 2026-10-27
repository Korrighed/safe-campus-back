# T016 — Désigner les comptes par leur identifiant dans l'audit

Statut : à faire
Story : US-06 · Exigences : NF-02 (étendu aux comptes)
Dépend de : T014, T015
Parallélisable : non
Exécutant : back

## Objectif

Le canal `audit` n'écrit plus aucune adresse e-mail, nom, prénom ni téléphone de compte. Un compte est désigné par son identifiant (et son rôle pour l'auteur). Constat d'audit : les e-mails étaient stockés dans les journaux.

## Constat de départ

| # | Où | Ce qui est écrit aujourd'hui |
|---|---|---|
| 1 | `AuditService::auteur()` | `email` de l'auteur, sur chaque événement. |
| 2 | `AuditService::decrire()` | Libellé de la cible = `email` pour un `User`. |
| 3 | `AuditObserver` sur `User` | `valeurs`, `avant`, `apres` avec `email`, `nom`, `prenom`, `telephone`. |
| 4 | `AuditAuthentification::handleFailed` | `email_tente` : l'adresse saisie, qui peut contenir un mot de passe tapé au mauvais endroit. |

Hors périmètre (à signaler seulement) : les e-mails des contacts de l'annuaire (modèle `Contact`), les entrées déjà écrites (expirent à 365 jours), la sortie console de `comptes:purger-inactifs`.

## Fichiers à lire

- `app/Services/AuditService.php` — `CHAMPS_CONTENU`, `nettoyer()`, `decrire()`, `auteur()`.
- `app/Observers/AuditObserver.php`.
- `app/Listeners/AuditAuthentification.php`.
- `app/Services/SuppressionCompteService.php` — appels `decrire($compte)` (lignes ~116-117).
- `tests/Feature/AuditLogTest.php` — assertions sur `auteur.email`, `cible.libelle`, `email_tente`, `apres` d'un `User` (lignes ~73, 102, 113, 126, 162, 169).
- `tests/Feature/AuditHistoireTest.php` — assertion `auteur.email` (ligne ~106).
- `docs/securite-rgpd.md` — § 7, registre des traitements (ligne « Journal d'audit »).
- `docs/deploiement.md` — section « Logs d'audit » (ligne « Contenu »).
- `resources/views/legal/_cgu.blade.php`, `_mentions-legales.blade.php` — mentions du journal.

## Fichiers cibles

| Fichier | Action | Ce qui change |
|---|---|---|
| `app/Services/AuditService.php` | modifier | Auteur sans e-mail, champs personnels masqués, libellé sans e-mail. |
| `app/Listeners/AuditAuthentification.php` | modifier | `email_tente` remplacé. |
| `tests/Feature/AuditLogTest.php`, `tests/Feature/AuditHistoireTest.php` | modifier | Assertions adaptées, tests ajoutés. |
| `docs/securite-rgpd.md`, `docs/deploiement.md`, `docs/TRD.md`, `docs/PRD.md` | modifier | Règle documentée. |
| `resources/views/legal/*.blade.php` | modifier si besoin | Seulement si une phrase affirme que le journal contient l'e-mail. |

## Étapes

1. `AuditService.php`
   - Ajouter, sous `CHAMPS_CONTENU` :

```php
    /**
     * Donnees personnelles d'un compte : jamais ecrites dans les logs, ni en
     * valeur ni en libelle. Un compte est designe par son identifiant.
     *
     * @var array<class-string<Model>, list<string>>
     */
    public const CHAMPS_PERSONNELS = [
        User::class => ['nom', 'prenom', 'email', 'telephone'],
    ];
```

   - Ajouter une méthode privée `champsMasques(Model $model): array` qui retourne `array_merge(self::CHAMPS_CONTENU[$model::class] ?? [], self::CHAMPS_PERSONNELS[$model::class] ?? [])`.
   - `nettoyer(array $attributs, ?Model $model = null)` : remplacer `self::CHAMPS_CONTENU[$model::class] ?? []` par `$this->champsMasques($model)`.
   - `decrire()` : `$masque = $this->champsMasques($model) !== [];` et retirer `$model->getAttribute('email')` de la chaîne de libellé (la chaîne devient `ref`, `titre`, `libelle`, `numero`). Pour un `User`, la cible ne porte donc que `type` et `id`.
   - `auteur()` : retourner `['id' => $user->id, 'role' => $user->role?->value]` (supprimer la clé `email`).
   - Mettre à jour les docblocks : « qui, quoi, sur quoi » : l'auteur est un identifiant et un rôle.
2. `AuditAuthentification::handleFailed` : remplacer le corps par :

```php
        // Jamais l'adresse saisie (elle peut contenir un mot de passe tape au
        // mauvais endroit) : l'identifiant du compte s'il existe, sinon rien.
        $compte = $event->user instanceof User ? $event->user : null;

        $this->audit->ecrire('auth.echec', null, [
            'compte_id' => $compte?->getKey(),
            'compte_connu' => $compte !== null,
        ]);
```

   Mettre à jour le commentaire de classe si besoin. `ecrire()` retire déjà les valeurs `null` : `compte_id` disparaît quand le compte est inconnu.
3. `SuppressionCompteService` : ne rien changer (les `decrire()` deviennent automatiquement sans libellé). Vérifier par lecture qu'aucun autre appel n'écrit un e-mail dans l'audit : `grep -n "email" app/Services/SuppressionCompteService.php` ne doit plus montrer d'écriture dans `ecrire(`.
4. Tests, `AuditLogTest.php` : adapter et ajouter (le handler en mémoire existe) :
   - ligne ~73 : `$this->assertSame($webmaster->id, $ligne->context['auteur']['id']);` et `assertArrayNotHasKey('email', $ligne->context['auteur']);`
   - lignes ~102 et ~113 (`cible.libelle` = e-mail) : `assertSame($user->id, $ligne->context['cible']['id']);` et `assertArrayNotHasKey('libelle', $ligne->context['cible']);`
   - ligne ~126 (`apres === ['nom' => 'Nouveau']`) : `assertSame(['nom'], $this->ligne('User.modifie')->context['champs_modifies']);` et `assertArrayNotHasKey('apres', ...)`.
   - ligne ~162 : remplacer l'assertion sur `email_tente` par `assertSame($user->id, $echec->context['compte_id']);` et `assertTrue($echec->context['compte_connu']);`.
   - ligne ~169 : `assertSame($user->id, $this->ligne('auth.connexion')?->context['cible']['id']);`
   - ajouter `test_aucune_adresse_email_n_apparait_dans_les_logs` : créer un `User::factory()` avec `email => 'confidentiel.<uniqid>@test.nc'`, `nom`, `prenom`, `telephone` reconnaissables ; le modifier (`update(['nom' => ...])`), le promouvoir (`update(['role' => ...])`), le supprimer ; `actingAs` un autre compte ; pour chaque record, `json_encode($record->context)` ne contient ni l'e-mail, ni le nom, ni le prénom, ni le téléphone, ni l'e-mail de l'auteur.
   - ajouter `test_un_echec_de_connexion_sans_compte_ne_garde_que_l_absence_de_compte` : `Livewire::test(Login::class)->fillForm(['email' => 'inconnu.<uniqid>@test.nc', 'password' => 'Mauvais-Mot-De-Passe!'])->call('authenticate')` ; la ligne `auth.echec` a `compte_connu === false`, pas de `compte_id`, et le JSON ne contient pas l'adresse saisie.
5. `AuditHistoireTest.php` ligne ~106 : `assertSame($relecteur->id, $ligne->context['auteur']['id']);`.
6. Chercher toute autre lecture de `auteur.email` ou `libelle` dans les tests : `grep -rn "\['email'\]\|email_tente" tests/`. Corriger chaque occurrence.
7. Documentation :
   - `docs/securite-rgpd.md` § 7 : « Contenu d'une entrée » → action, objet concerné, auteur (identifiant et rôle), adresse IP, détails. « Exclusions » : ajouter « Jamais d'adresse e-mail, de nom, de prénom ni de téléphone de compte : un compte est désigné par son identifiant. » Ajouter une note : les entrées écrites avant cette règle contiennent des adresses e-mail jusqu'à leur purge à 365 jours. Registre des traitements (ligne « Journal d'audit », ~l. 323) : remplacer « Identité » par « Identifiant du compte, rôle ».
   - `docs/deploiement.md` (ligne « Contenu ») : « échec de connexion (identifiant du compte s'il existe) » à la place de « (email tenté) ».
   - `docs/PRD.md` NF-02 et `docs/TRD.md` T-09 : ajouter la phrase « Aucune donnée personnelle de compte (e-mail, nom, prénom, téléphone) : identifiant seulement. »
   - `resources/views/legal/_cgu.blade.php` et `_mentions-legales.blade.php` : `grep -niE "journal|audit|adresse" resources/views/legal/*.blade.php`. Ne modifier une phrase que si elle affirme que le journal contient l'adresse e-mail. Sinon, le noter dans « Notes d'exécution ». Signaler (sans modifier) tout texte équivalent du front `Safe-Campus-front`.

## Critères d'acceptation

- [ ] AC-01 : Étant donné un événement quelconque, quand on lit le canal `audit`, alors `auteur` contient `id` et `role`, jamais d'e-mail.
- [ ] AC-02 : Étant donné la création, la modification ou la suppression d'un compte, alors aucune valeur de nom, prénom, e-mail ni téléphone n'est écrite.
- [ ] AC-03 : Étant donné un échec de connexion, alors l'adresse saisie n'est pas écrite ; `compte_id` apparaît seulement si le compte existe.
- [ ] AC-04 : Le changement de rôle d'un compte reste tracé (`avant` et `apres` sur `role`).
- [ ] AC-05 : La documentation décrit la règle et la rétention des anciennes entrées.

## Vérification

Aucune exécution pendant le lot (consigne). Contrôle autorisé : `php -l` sur les fichiers PHP modifiés. En fin de lot :

```bash
docker compose exec sc_back php artisan test --filter="AuditLogTest|AuditHistoireTest|UserResourceTest|PurgeComptesInactifsTest"
```

Parcours navigateur : aucun (pas d'impact visible).

## Hors périmètre

- Les e-mails des contacts de l'annuaire (`Contact`) et leurs téléphones.
- La sortie console de `comptes:purger-inactifs` (e-mails affichés dans le terminal, hors canal `audit`).
- Le nettoyage des fichiers de logs existants.
- L'adresse IP, déjà documentée comme donnée journalisée.

## Notes d'exécution

- Exécution back, 2026-10-28, branche `feature/role-relecteur`. Étapes 1 à 6 faites. Étape 7 (docs) retirée du périmètre par l'orchestrateur (décision 1) : traitée par un agent executor, aucun fichier `docs/` ni `resources/views/legal/` touché.
- Fichiers modifiés : `app/Services/AuditService.php`, `app/Listeners/AuditAuthentification.php`, `tests/Feature/AuditLogTest.php`, `tests/Feature/AuditHistoireTest.php`.
- Vérification : `php -l` sur les 4 fichiers, sans erreur. Aucun test lancé (consigne utilisateur). Les assertions ajoutées ou modifiées ne sont donc pas exécutées.
- Étape 1 : `CHAMPS_PERSONNELS`, `champsMasques()`, `nettoyer()`, `decrire()` et `auteur()` adaptés. `decrire()` d'un `User` ne renvoie plus que `type` et `id`.
- Étape 2 : `handleFailed` écrit `compte_id` (si le compte existe) et `compte_connu`. L'adresse saisie n'est plus écrite.
- Étape 3 : `SuppressionCompteService` inchangé. Les `email` restants (lignes 51, 57, 82) sont des valeurs de retour ou d'affichage, aucun n'entre dans `ecrire()`.
- Étape 4 : assertions de `AuditLogTest` adaptées. Ajout de `test_aucune_adresse_email_n_apparait_dans_les_logs` et de `test_un_echec_de_connexion_sans_compte_ne_garde_que_l_absence_de_compte`. Renommage de `test_une_suppression_garde_un_libelle_lisible` en `test_une_suppression_garde_l_identifiant_sans_libelle` : le libellé n'existe plus.
- Étape 5 : `AuditHistoireTest` ligne 107, `auteur.id`.
- Étape 6 : grep exhaustif (`['email']`, `email_tente`, `auteur`, `cible`, `apres`). Plus aucune lecture d'e-mail de compte dans le canal audit. Les `['email']` restants visent des formulaires ou la base.
- Relecture de fin de fichier : aucune balise parasite.
- Point 1, docs : retiré de mon périmètre (décision 1 de l'orchestrateur). Rien touché dans `docs/` hors cette section.
- Point 2, légal : aucune phrase de `resources/views/legal/*.blade.php` n'affirme que le journal contient l'e-mail. Rien modifié. `_cgu.blade.php:35` dit « Journal d'audit (identité, action, adresse IP, date) ». « Identité » n'est pas une adresse e-mail, donc non modifié. Le terme devient inexact après T016 : à trancher par l'executor docs, hors de mon périmètre.
- Point 3, front (lecture seule) : `Safe-Campus-front/app/pages/donnees-personnelles.vue:70` parle de la journalisation d'IP par l'hébergeur, pas du contenu du journal. Rien à signaler.
- Point 4, Contact, correction : j'ai écrit que `Contact` perdait son e-mail en libellé. Faux. Le modèle `Contact` n'a pas de colonne `email` (colonne `mail`, `app/Models/Contact.php`). Son libellé était déjà `ref` (slug du nom, `Contact.php:59`) avant et après T016. Décision 2 de l'orchestrateur appliquée : `email` remis en tête de la chaîne de `decrire()`, sans effet sur `Contact`. Option A retenue. Pré-existant, hors périmètre (lecture du code, non testé) : `Contact.mail` est écrit dans `valeurs` du canal audit (`Contact.cree`, `Contact.modifie`, `Contact.supprime`). Les e-mails de contacts restent hors périmètre, comme prévu par la tâche.
- Test ajouté : `test_le_libelle_des_autres_modeles_est_inchange` (`AuditLogTest`). Pour `Theme`, `cible.libelle` vaut `ref` : `ref` précède `libelle` dans la chaîne. Aucun test écrit ou adapté ne référence `Contact`. Pas d'assertion Contact : `Contact` n'a pas de colonne `email`, donc un tel test ne testerait pas la chaîne.
- Point 5, pré-existant, hors périmètre : `decrire()` lit `numero` pour le libellé si `libelle` est vide. Un numéro de téléphone de contact peut donc être écrit. Inchangé par T016.
- Les noms de champs personnels (`nom`, `email`, etc.) restent dans `champs_modifies`. Seuls les noms sont écrits, conforme à l'étape 1 et à AC-02.
- Étape 7 (executor) : docs mises à jour. `docs/securite-rgpd.md` § 7 : contenu, exclusions (adresse, nom, prénom, téléphone de compte ; `auth.echec`), note de rétention (entrées antérieures : e-mails, saisies de connexion, mot de passe possible), limite Contact. Le bloc de note était inséré entre deux lignes du tableau : déplacé sous le tableau pour le rendre contigu. Registre, ligne « Journal d'audit » : « Identifiant du compte, rôle, action, IP, date ». `docs/deploiement.md` (ligne « Contenu ») : « identifiant du compte s'il existe ». `docs/PRD.md` NF-02 et `docs/TRD.md` T-09 : phrase ajoutée. Hors liste de la tâche, modifiés car contredits par la règle : `docs/PRD.md` § 3 (ligne « Les comptes `User` … restent journalisés » supprimée) et `docs/TRD.md` § 10 (risque `User` remplacé par un risque `Contact`, gravité et effort inchangés). `resources/views/legal/_cgu.blade.php:35` : « identité » remplacé par « identifiant du compte ». `_mentions-legales.blade.php` inchangé. `docs/IMPLEMENTATION-PLAN.md` non touché. Aucune vérification de code lancée. Non commité.
