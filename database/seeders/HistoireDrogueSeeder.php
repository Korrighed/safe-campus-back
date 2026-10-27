<?php

namespace Database\Seeders;

use App\Enums\ChoixIssue;

/**
 * Histoire de demonstration « Le joint qui tourne » (sous-theme drogue) :
 * 4 scenes, 12 choix, vecue a la premiere personne puis en temoin du coloc.
 * La soiree et la recherche d'aide sont des illustrations JPG generees, le
 * lendemain et la messagerie des SVG animes (CSS uniquement, sans script ni
 * ressource externe, animations figees sous prefers-reduced-motion).
 * Fonctionnement commun dans HistoireDemoSeeder.
 *
 * @phpstan-import-type DonneesScene from HistoireDemoSeeder
 */
class HistoireDrogueSeeder extends HistoireDemoSeeder
{
    /**
     * @var array<string, DonneesScene>
     */
    private const SCENES = [
        'soiree' => [
            'titre' => '[DEMO] Soirée en coloc',
            'dialogue_text' => "Soirée chez ton coloc. Un joint commence à tourner et arrive jusqu'à toi.",
            'image' => 'soiree.jpg',
            'est_initiale' => true,
            'choix' => [
                ['text_choix' => 'Dire « non merci » et le passer', 'suivante' => 'coloc', 'issue' => null],
                ['text_choix' => 'Accepter pour faire comme tout le monde', 'suivante' => 'lendemain', 'issue' => null],
                ['text_choix' => 'Prendre aussi le cachet que quelqu\'un propose « pour être bien »', 'suivante' => null, 'issue' => ChoixIssue::Defavorable],
            ],
        ],
        'lendemain' => [
            'titre' => '[DEMO] Le lendemain',
            'dialogue_text' => "Le lendemain, cours à 7h30. Tu as du mal à te concentrer. Ton coloc t'explique qu'il fume tous les soirs « pour arriver à dormir ».",
            'image' => 'lendemain.svg',
            'choix' => [
                ['text_choix' => 'Te renseigner sur les effets du cannabis sur le sommeil', 'suivante' => 'coloc', 'issue' => null],
                ['text_choix' => 'Faire pareil le soir pour mieux dormir', 'suivante' => null, 'issue' => ChoixIssue::Defavorable],
                ['text_choix' => 'En parler franchement avec lui', 'suivante' => 'coloc', 'issue' => null],
            ],
        ],
        'coloc' => [
            'titre' => '[DEMO] Le message du coloc',
            'dialogue_text' => "Quelques semaines plus tard, ton coloc a raté deux partiels. Il t'écrit qu'il n'arrive plus à s'en passer et qu'il a honte.",
            'image' => 'coloc.svg',
            'choix' => [
                ['text_choix' => "L'écouter, sans le juger", 'suivante' => 'aide', 'issue' => null],
                ['text_choix' => "Lui répondre que c'est son problème", 'suivante' => null, 'issue' => ChoixIssue::Defavorable],
                ['text_choix' => "Lui proposer de chercher de l'aide ensemble", 'suivante' => 'aide', 'issue' => null],
            ],
        ],
        'aide' => [
            'titre' => '[DEMO] Trouver de l\'aide',
            'dialogue_text' => "Il est d'accord pour en parler à quelqu'un, mais il ne sait pas à qui.",
            'image' => 'aide.jpg',
            'choix' => [
                ['text_choix' => "Lui proposer de l'accompagner chez DECLIC", 'suivante' => null, 'issue' => ChoixIssue::Favorable],
                ['text_choix' => "Lui donner le contact de l'ASS-NC et rester disponible", 'suivante' => null, 'issue' => ChoixIssue::Favorable],
                ['text_choix' => "Lui conseiller d'arrêter tout seul du jour au lendemain, sans en parler", 'suivante' => null, 'issue' => ChoixIssue::Defavorable],
            ],
        ],
    ];

    protected function ref(): string
    {
        return 'demo_joint_qui_tourne';
    }

    protected function titre(): string
    {
        return 'Le joint qui tourne (démo)';
    }

    protected function sousThemeRef(): string
    {
        return 'drogue';
    }

    protected function dossier(): string
    {
        return 'demo-joint-qui-tourne';
    }

    protected function scenes(): array
    {
        return self::SCENES;
    }
}
