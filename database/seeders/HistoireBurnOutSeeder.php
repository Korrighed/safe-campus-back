<?php

namespace Database\Seeders;

use App\Enums\ChoixIssue;

/**
 * Histoire de demonstration « Tout tenir » (sous-theme burn_out) : 4 scenes,
 * 12 choix. Le bureau de nuit et le cabinet medical sont des illustrations JPG
 * generees, l'agenda et le tri des SVG animes (CSS uniquement, sans script ni
 * ressource externe, animations figees sous prefers-reduced-motion).
 * Fonctionnement commun dans HistoireDemoSeeder.
 *
 * @phpstan-import-type DonneesScene from HistoireDemoSeeder
 */
class HistoireBurnOutSeeder extends HistoireDemoSeeder
{
    /**
     * @var array<string, DonneesScene>
     */
    private const SCENES = [
        'agenda' => [
            'titre' => '[DEMO] Un agenda plein',
            'dialogue_text' => "Ta licence, ton job au supermarché le soir, la trésorerie de l'asso… Ton agenda est plein jusqu'à 23h. Un collègue te demande de le remplacer samedi.",
            'image' => 'agenda.svg',
            'est_initiale' => true,
            'choix' => [
                ['text_choix' => 'Dire oui, comme toujours', 'suivante' => 'fatigue', 'issue' => null],
                ['text_choix' => "Dire non : samedi, c'est ton seul jour de repos", 'suivante' => 'bilan', 'issue' => null],
                ['text_choix' => 'Accepter, et tenir à coups de boissons énergisantes', 'suivante' => null, 'issue' => ChoixIssue::Defavorable],
            ],
        ],
        'fatigue' => [
            'titre' => '[DEMO] Vidé·e',
            'dialogue_text' => "Trois semaines plus tard, tu as mal à la tête, tu relis la même ligne dix fois et tu t'énerves pour un rien. Tu te sens vidé·e.",
            'image' => 'fatigue.jpg',
            'choix' => [
                ['text_choix' => 'Tenir encore : les vacances sont dans un mois', 'suivante' => null, 'issue' => ChoixIssue::Defavorable],
                ['text_choix' => 'Faire la liste de ce qui peut attendre ou être délégué', 'suivante' => 'bilan', 'issue' => null],
                ['text_choix' => 'En parler au médecin universitaire', 'suivante' => 'medecin', 'issue' => null],
            ],
        ],
        'bilan' => [
            'titre' => '[DEMO] Faire le tri',
            'dialogue_text' => "Sur papier, tout devient plus clair : tout n'est pas indispensable. Mais lâcher quelque chose te donne l'impression d'échouer.",
            'image' => 'bilan.svg',
            'choix' => [
                ['text_choix' => "Annoncer à l'asso que tu passes le relais pour la trésorerie", 'suivante' => 'medecin', 'issue' => null],
                ['text_choix' => 'Tout garder, et dormir moins', 'suivante' => null, 'issue' => ChoixIssue::Defavorable],
                ['text_choix' => 'Bloquer dans ton agenda une soirée par semaine sans rien', 'suivante' => 'medecin', 'issue' => null],
            ],
        ],
        'medecin' => [
            'titre' => '[DEMO] Chez le médecin',
            'dialogue_text' => "Le médecin t'écoute. Il t'explique que l'épuisement se soigne, et qu'il vaut mieux agir avant de craquer.",
            'image' => 'medecin.jpg',
            'choix' => [
                ['text_choix' => 'Suivre ses conseils et revenir le voir dans deux semaines', 'suivante' => null, 'issue' => ChoixIssue::Favorable],
                ['text_choix' => "Demander un aménagement d'études auprès de la scolarité", 'suivante' => null, 'issue' => ChoixIssue::Favorable],
                ['text_choix' => 'Repartir sans rien changer, « ça ira mieux après les partiels »', 'suivante' => null, 'issue' => ChoixIssue::Defavorable],
            ],
        ],
    ];

    protected function ref(): string
    {
        return 'demo_tout_tenir';
    }

    protected function titre(): string
    {
        return 'Tout tenir (démo)';
    }

    protected function sousThemeRef(): string
    {
        return 'burn_out';
    }

    protected function dossier(): string
    {
        return 'demo-tout-tenir';
    }

    protected function scenes(): array
    {
        return self::SCENES;
    }
}
