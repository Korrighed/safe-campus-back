<?php

namespace Database\Seeders;

use App\Enums\ChoixIssue;

/**
 * Histoire de demonstration « Juste une blague » (sous-theme
 * violences_sexistes) : 4 scenes, 12 choix, vecue a la premiere personne lors
 * d'un projet de groupe. Le camarade n'a volontairement pas de prenom.
 * La reunion et la cafeteria sont des illustrations JPG generees, la
 * messagerie et l'orientation des SVG animes (CSS uniquement, sans script ni
 * ressource externe, animations figees sous prefers-reduced-motion).
 * Fonctionnement commun dans HistoireDemoSeeder.
 *
 * @phpstan-import-type DonneesScene from HistoireDemoSeeder
 */
class HistoireViolencesSexistesSeeder extends HistoireDemoSeeder
{
    /**
     * @var array<string, DonneesScene>
     */
    private const SCENES = [
        'reunion' => [
            'titre' => '[DEMO] La réunion de projet',
            'dialogue_text' => 'Réunion de projet. Tu proposes une idée, personne ne réagit. Deux minutes plus tard, un camarade la reprend mot pour mot : « Bonne idée ! »',
            'image' => 'reunion.jpg',
            'est_initiale' => true,
            'choix' => [
                ['text_choix' => 'Dire calmement : « C\'est ce que je proposais tout à l\'heure. »', 'suivante' => 'blague', 'issue' => null],
                ['text_choix' => 'Laisser couler pour cette fois', 'suivante' => 'blague', 'issue' => null],
                ['text_choix' => "Te dire que c'est sûrement toi qui t'exprimes mal", 'suivante' => null, 'issue' => ChoixIssue::Defavorable],
            ],
        ],
        'blague' => [
            'titre' => '[DEMO] Juste une blague',
            'dialogue_text' => 'Plus tard, devant le groupe : « Laisse, la partie technique c\'est pas pour toi, occupe-toi plutôt des slides. » Les autres rient.',
            'image' => 'blague.jpg',
            'choix' => [
                ['text_choix' => "Répondre que ce n'est pas drôle et que tu gardes ta partie", 'suivante' => 'soutien', 'issue' => null],
                ['text_choix' => "Rire aussi, pour ne pas faire d'histoires", 'suivante' => 'soutien', 'issue' => null],
                ['text_choix' => 'Faire les slides et abandonner la partie technique', 'suivante' => null, 'issue' => ChoixIssue::Defavorable],
            ],
        ],
        'soutien' => [
            'titre' => '[DEMO] Un message le soir',
            'dialogue_text' => "Le soir, une fille du groupe t'écrit. Elle aussi a déjà eu droit à ce genre de remarques.",
            'image' => 'soutien.svg',
            'choix' => [
                ['text_choix' => 'Noter ensemble les faits, avec les dates', 'suivante' => 'referent', 'issue' => null],
                ['text_choix' => "Lui répondre que ce n'est pas si grave", 'suivante' => null, 'issue' => ChoixIssue::Defavorable],
                ['text_choix' => "Proposer d'en parler ensemble à l'enseignant responsable", 'suivante' => 'referent', 'issue' => null],
            ],
        ],
        'referent' => [
            'titre' => '[DEMO] À qui en parler',
            'dialogue_text' => 'Vous avez vos notes. Reste à savoir vers qui vous tourner.',
            'image' => 'referent.svg',
            'choix' => [
                ['text_choix' => "En parler au service Médiation de l'UNC", 'suivante' => null, 'issue' => ChoixIssue::Favorable],
                ['text_choix' => 'Contacter le CIDFE pour connaître vos droits', 'suivante' => null, 'issue' => ChoixIssue::Favorable],
                ['text_choix' => "Te dire que ça ne sert à rien, c'est comme ça partout", 'suivante' => null, 'issue' => ChoixIssue::Defavorable],
            ],
        ],
    ];

    protected function ref(): string
    {
        return 'demo_juste_une_blague';
    }

    protected function titre(): string
    {
        return 'Juste une blague (démo)';
    }

    protected function sousThemeRef(): string
    {
        return 'violences_sexistes';
    }

    protected function dossier(): string
    {
        return 'demo-juste-une-blague';
    }

    protected function scenes(): array
    {
        return self::SCENES;
    }
}
