<?php

namespace Database\Seeders;

use App\Enums\ChoixIssue;

/**
 * Histoire de demonstration « Les volets fermés » (sous-theme depression),
 * vecue en temoin d'un ami, Sam : 4 scenes, 12 choix. La chambre, le banc et
 * la salle d'attente sont des illustrations JPG generees, la messagerie un SVG
 * anime (CSS uniquement, sans script ni ressource externe, animations figees
 * sous prefers-reduced-motion). Fonctionnement commun dans HistoireDemoSeeder.
 *
 * @phpstan-import-type DonneesScene from HistoireDemoSeeder
 */
class HistoireDepressionSeeder extends HistoireDemoSeeder
{
    /**
     * @var array<string, DonneesScene>
     */
    private const SCENES = [
        'absences' => [
            'titre' => '[DEMO] Une place vide',
            'dialogue_text' => 'Ça fait trois semaines que Sam ne vient plus en cours. Il répond de moins en moins aux messages. Le dernier : « jsuis juste fatigué ».',
            'image' => 'absences.svg',
            'est_initiale' => true,
            'choix' => [
                ['text_choix' => 'Passer chez lui', 'suivante' => 'chambre', 'issue' => null],
                ['text_choix' => 'Lui proposer un café par message', 'suivante' => 'parler', 'issue' => null],
                ['text_choix' => 'Te dire que ça va passer tout seul', 'suivante' => null, 'issue' => ChoixIssue::Defavorable],
            ],
        ],
        'chambre' => [
            'titre' => '[DEMO] Les volets fermés',
            'dialogue_text' => "Sam t'ouvre. Les volets sont fermés, la vaisselle s'entasse. Il n'a plus envie de rien, même de ce qu'il aimait avant.",
            'image' => 'chambre.jpg',
            'choix' => [
                ['text_choix' => 'Lui dire « secoue-toi, ça va aller »', 'suivante' => null, 'issue' => ChoixIssue::Defavorable],
                ['text_choix' => "T'asseoir et lui demander comment il se sent vraiment", 'suivante' => 'parler', 'issue' => null],
                ['text_choix' => "Lui proposer d'ouvrir les volets et de faire un petit tour dehors", 'suivante' => 'parler', 'issue' => null],
            ],
        ],
        'parler' => [
            'titre' => '[DEMO] Sur le banc',
            'dialogue_text' => 'Sam finit par raconter que ça dure depuis des semaines : il dort mal, plus rien n\'avance, et il se sent nul.',
            'image' => 'parler.jpg',
            'choix' => [
                ['text_choix' => "Lui dire que ce qu'il vit a un nom, et que ça se soigne", 'suivante' => 'rdv', 'issue' => null],
                ['text_choix' => 'Lui promettre de garder ça pour toi et ne rien faire', 'suivante' => null, 'issue' => ChoixIssue::Defavorable],
                ['text_choix' => "Lui proposer de chercher ensemble qui peut l'aider", 'suivante' => 'rdv', 'issue' => null],
            ],
        ],
        'rdv' => [
            'titre' => '[DEMO] Le premier rendez-vous',
            'dialogue_text' => "Sam veut bien voir quelqu'un, mais il redoute d'y aller seul.",
            'image' => 'rdv.jpg',
            'choix' => [
                ['text_choix' => "L'accompagner au Service Santé Étudiante de l'UNC", 'suivante' => null, 'issue' => ChoixIssue::Favorable],
                ['text_choix' => "L'aider à appeler SOS Écoute le soir même", 'suivante' => null, 'issue' => ChoixIssue::Favorable],
                ['text_choix' => "Te dire que c'est réglé maintenant que vous avez parlé", 'suivante' => null, 'issue' => ChoixIssue::Defavorable],
            ],
        ],
    ];

    protected function ref(): string
    {
        return 'demo_volets_fermes';
    }

    protected function titre(): string
    {
        return 'Les volets fermés (démo)';
    }

    protected function sousThemeRef(): string
    {
        return 'depression';
    }

    protected function dossier(): string
    {
        return 'demo-volets-fermes';
    }

    protected function scenes(): array
    {
        return self::SCENES;
    }
}
