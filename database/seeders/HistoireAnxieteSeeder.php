<?php

namespace Database\Seeders;

use App\Enums\ChoixIssue;

/**
 * Histoire de demonstration « La veille du partiel » (sous-theme anxiete) :
 * 4 scenes, 12 choix, illustrees par des SVG animes (CSS uniquement, sans
 * script ni ressource externe, animations figees sous prefers-reduced-motion).
 * Fonctionnement commun dans HistoireDemoSeeder.
 *
 * @phpstan-import-type DonneesScene from HistoireDemoSeeder
 */
class HistoireAnxieteSeeder extends HistoireDemoSeeder
{
    /**
     * @var array<string, DonneesScene>
     */
    private const SCENES = [
        'veille' => [
            'titre' => '[DEMO] La veille du partiel',
            'dialogue_text' => "Il est 23h. Ton partiel commence demain à 8h. Ton téléphone n'arrête pas de vibrer : le groupe de promo partage ses fiches, quelqu'un demande « vous avez tout revu ? ». Ton cœur bat vite, tu relis la même page pour la troisième fois.",
            'image' => 'veille.svg',
            'est_initiale' => true,
            'choix' => [
                ['text_choix' => 'Couper les notifications et faire une pause', 'suivante' => 'pause', 'issue' => null],
                ['text_choix' => 'Continuer à réviser toute la nuit, avec un café de plus', 'suivante' => 'nuit', 'issue' => null],
                ['text_choix' => 'Écrire à un proche que tu stresses beaucoup', 'suivante' => null, 'issue' => ChoixIssue::Favorable],
            ],
        ],
        'pause' => [
            'titre' => '[DEMO] Une pause',
            'dialogue_text' => 'Tu poses ton téléphone. Dans ta tête, ça tourne en boucle : « si je rate, je perds mon année ». Ta gorge se serre.',
            'image' => 'pause.svg',
            'choix' => [
                ['text_choix' => 'Suivre le cercle : inspirer, expirer, quelques minutes, puis aller dormir', 'suivante' => 'matin', 'issue' => null],
                ['text_choix' => "Te dire que tu n'as pas le temps pour ça et reprendre tes fiches", 'suivante' => 'nuit', 'issue' => null],
                ['text_choix' => "Chercher « symptômes crise d'angoisse » sur Internet pendant une heure", 'suivante' => null, 'issue' => ChoixIssue::Defavorable],
            ],
        ],
        'nuit' => [
            'titre' => '[DEMO] Nuit blanche',
            'dialogue_text' => "3h du matin. Tes mains tremblent, ta respiration est courte et les lignes se mélangent. Tu n'arrives plus à retenir quoi que ce soit.",
            'image' => 'nuit.svg',
            'choix' => [
                ['text_choix' => "Appeler une ligne d'écoute ou réveiller ton coloc pour en parler", 'suivante' => null, 'issue' => ChoixIssue::Favorable],
                ['text_choix' => "Prendre un cachet qu'un ami t'a donné « pour te calmer »", 'suivante' => null, 'issue' => ChoixIssue::Defavorable],
                ['text_choix' => "Te forcer à continuer, tu dormiras après l'examen", 'suivante' => 'matin', 'issue' => null],
            ],
        ],
        'matin' => [
            'titre' => "[DEMO] Le matin de l'examen",
            'dialogue_text' => "Devant la salle, tout le monde révise à voix haute. Ton cœur s'emballe, tu as envie de partir en courant.",
            'image' => 'matin.svg',
            'choix' => [
                ['text_choix' => 'Prendre un moment pour respirer lentement, puis entrer', 'suivante' => null, 'issue' => ChoixIssue::Favorable],
                ['text_choix' => "Rentrer chez toi sans passer l'examen et n'en parler à personne", 'suivante' => null, 'issue' => ChoixIssue::Defavorable],
                ['text_choix' => 'Prévenir le surveillant que tu ne te sens pas bien', 'suivante' => null, 'issue' => ChoixIssue::Favorable],
            ],
        ],
    ];

    protected function ref(): string
    {
        return 'demo_veille_du_partiel';
    }

    protected function titre(): string
    {
        return 'La veille du partiel (démo)';
    }

    protected function sousThemeRef(): string
    {
        return 'anxiete';
    }

    protected function dossier(): string
    {
        return 'demo-veille-du-partiel';
    }

    protected function scenes(): array
    {
        return self::SCENES;
    }
}
