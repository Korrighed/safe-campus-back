<?php

namespace Database\Seeders;

use App\Enums\ChoixIssue;

/**
 * Histoire de demonstration « La pause clope » (sous-theme tabac) : 4 scenes,
 * 12 choix. La BU et le banc sont des illustrations JPG generees, l'escalier
 * et l'envie des SVG animes (CSS uniquement, sans script ni ressource externe,
 * animations figees sous prefers-reduced-motion). Fonctionnement commun dans
 * HistoireDemoSeeder.
 *
 * @phpstan-import-type DonneesScene from HistoireDemoSeeder
 */
class HistoireTabacSeeder extends HistoireDemoSeeder
{
    /**
     * @var array<string, DonneesScene>
     */
    private const SCENES = [
        'pause' => [
            'titre' => '[DEMO] La pause clope',
            'dialogue_text' => "Entre deux heures de révision à la BU, tes amis sortent fumer. Tu n'as pas vraiment envie, mais tu as pris l'habitude de les suivre. Ce matin, tu en es déjà à ta troisième cigarette.",
            'image' => 'pause.jpg',
            'est_initiale' => true,
            'choix' => [
                ['text_choix' => "Sortir avec eux, mais juste pour prendre l'air", 'suivante' => 'envie', 'issue' => null],
                ['text_choix' => "En prendre une, comme d'habitude", 'suivante' => 'escalier', 'issue' => null],
                ['text_choix' => 'Acheter un deuxième paquet « pour tenir pendant les partiels »', 'suivante' => null, 'issue' => ChoixIssue::Defavorable],
            ],
        ],
        'escalier' => [
            'titre' => "[DEMO] L'escalier",
            'dialogue_text' => 'En remontant en salle, tu es essoufflé·e dès le deuxième étage. En regardant ton compte, tu réalises que les paquets pèsent lourd dans ton budget du mois.',
            'image' => 'escalier.svg',
            'choix' => [
                ['text_choix' => "Te dire qu'il est temps de réduire, et prévenir tes amis", 'suivante' => 'envie', 'issue' => null],
                ['text_choix' => "Te promettre d'arrêter « après les partiels »", 'suivante' => null, 'issue' => ChoixIssue::Defavorable],
                ['text_choix' => "Prendre rendez-vous au Service Santé Étudiante de l'UNC", 'suivante' => null, 'issue' => ChoixIssue::Favorable],
            ],
        ],
        'envie' => [
            'titre' => "[DEMO] L'envie",
            'dialogue_text' => "Le soir, l'envie arrive d'un coup. Tu as lu qu'une envie de fumer ne dure que quelques minutes.",
            'image' => 'envie.svg',
            'choix' => [
                ['text_choix' => 'Boire un grand verre d\'eau et marcher cinq minutes', 'suivante' => 'soutien', 'issue' => null],
                ['text_choix' => 'Aller chercher le paquet, « juste une »', 'suivante' => null, 'issue' => ChoixIssue::Defavorable],
                ['text_choix' => "Appeler une amie qui a arrêté l'an dernier", 'suivante' => 'soutien', 'issue' => null],
            ],
        ],
        'soutien' => [
            'titre' => '[DEMO] Un coup de main',
            'dialogue_text' => "Ton amie te raconte comment elle s'y est prise. Elle te parle de DECLIC : des consultations gratuites et confidentielles pour les moins de 25 ans.",
            'image' => 'soutien.jpg',
            'choix' => [
                ['text_choix' => 'Prendre rendez-vous chez DECLIC', 'suivante' => null, 'issue' => ChoixIssue::Favorable],
                ['text_choix' => 'Demander conseil au médecin universitaire sur les substituts nicotiniques', 'suivante' => null, 'issue' => ChoixIssue::Favorable],
                ['text_choix' => "Te dire que tu n'as besoin de personne, tu gères", 'suivante' => null, 'issue' => ChoixIssue::Defavorable],
            ],
        ],
    ];

    protected function ref(): string
    {
        return 'demo_pause_clope';
    }

    protected function titre(): string
    {
        return 'La pause clope (démo)';
    }

    protected function sousThemeRef(): string
    {
        return 'tabac';
    }

    protected function dossier(): string
    {
        return 'demo-pause-clope';
    }

    protected function scenes(): array
    {
        return self::SCENES;
    }
}
