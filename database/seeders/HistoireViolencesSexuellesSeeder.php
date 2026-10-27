<?php

namespace Database\Seeders;

use App\Enums\ChoixIssue;

/**
 * Histoire de demonstration « Le retour de soirée » (sous-theme
 * violences_sexuelles), vecue en temoin : 4 scenes, 12 choix. Rien n'est
 * montre ni suggere au-dela de l'insistance ; l'histoire porte sur
 * l'intervention du temoin, le consentement et le soutien sans culpabiliser.
 * Le taxi est une illustration JPG generee, les autres scenes des SVG animes
 * (CSS uniquement, sans script ni ressource externe, animations figees sous
 * prefers-reduced-motion). Fonctionnement commun dans HistoireDemoSeeder.
 *
 * @phpstan-import-type DonneesScene from HistoireDemoSeeder
 */
class HistoireViolencesSexuellesSeeder extends HistoireDemoSeeder
{
    /**
     * @var array<string, DonneesScene>
     */
    private const SCENES = [
        'soiree' => [
            'titre' => '[DEMO] Fin de soirée',
            'dialogue_text' => 'Fin de soirée. Ton amie a beaucoup bu et tient à peine debout. Un garçon que vous connaissez à peine insiste : « T\'inquiète, je la ramène chez moi. »',
            'image' => 'soiree.svg',
            'est_initiale' => true,
            'choix' => [
                ['text_choix' => 'Faire diversion : « Ah t\'es là ! On rentre ensemble, le taxi arrive. »', 'suivante' => 'retour', 'issue' => null],
                ['text_choix' => "Te dire qu'elle est majeure, ça la regarde", 'suivante' => null, 'issue' => ChoixIssue::Defavorable],
                ['text_choix' => "Prévenir l'organisateur ou la sécurité", 'suivante' => 'retour', 'issue' => null],
            ],
        ],
        'retour' => [
            'titre' => '[DEMO] Dans le taxi',
            'dialogue_text' => "Dans le taxi, elle te remercie. Il insistait depuis le début de la soirée, alors qu'elle avait dit non plusieurs fois.",
            'image' => 'retour.jpg',
            'choix' => [
                ['text_choix' => "Lui rappeler que dire non suffit, et que ce n'est pas sa faute", 'suivante' => 'lendemain', 'issue' => null],
                ['text_choix' => "Lui dire qu'elle n'aurait pas dû autant boire", 'suivante' => null, 'issue' => ChoixIssue::Defavorable],
                ['text_choix' => "Rester avec elle jusqu'à ce qu'elle soit en sécurité chez elle", 'suivante' => 'lendemain', 'issue' => null],
            ],
        ],
        'lendemain' => [
            'titre' => '[DEMO] Le lendemain',
            'dialogue_text' => "Le lendemain, elle t'écrit : elle a appris qu'il avait déjà fait ça avec d'autres filles, et elle ne sait pas quoi faire.",
            'image' => 'lendemain.svg',
            'choix' => [
                ['text_choix' => "L'écouter et lui demander ce dont elle a besoin", 'suivante' => 'aide', 'issue' => null],
                ['text_choix' => 'Publier le nom du garçon sur les réseaux pour prévenir tout le monde', 'suivante' => null, 'issue' => ChoixIssue::Defavorable],
                ['text_choix' => 'Lui proposer de trouver ensemble des personnes ressources', 'suivante' => 'aide', 'issue' => null],
            ],
        ],
        'aide' => [
            'titre' => '[DEMO] Être accompagnée',
            'dialogue_text' => 'Elle veut être conseillée, à son rythme, sans forcément porter plainte tout de suite.',
            'image' => 'aide.svg',
            'choix' => [
                ['text_choix' => "L'accompagner à SOS Victimes ou à l'ADAVI", 'suivante' => null, 'issue' => ChoixIssue::Favorable],
                ['text_choix' => "Lui parler du service Médiation de l'UNC pour signaler un étudiant", 'suivante' => null, 'issue' => ChoixIssue::Favorable],
                ['text_choix' => "Lui dire d'oublier, que ça finira par passer", 'suivante' => null, 'issue' => ChoixIssue::Defavorable],
            ],
        ],
    ];

    protected function ref(): string
    {
        return 'demo_retour_de_soiree';
    }

    protected function titre(): string
    {
        return 'Le retour de soirée (démo)';
    }

    protected function sousThemeRef(): string
    {
        return 'violences_sexuelles';
    }

    protected function dossier(): string
    {
        return 'demo-retour-de-soiree';
    }

    protected function scenes(): array
    {
        return self::SCENES;
    }
}
