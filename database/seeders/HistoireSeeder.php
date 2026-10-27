<?php

namespace Database\Seeders;

use App\Enums\ChoixIssue;

/**
 * Histoire de demonstration « Une soiree qui derape » : 3 scenes, 9 choix,
 * un parcours favorable et un defavorable. Sert a retrouver un jeu de donnees
 * de reference apres un `migrate:fresh --seed`. Fonctionnement commun dans
 * HistoireDemoSeeder.
 *
 * @phpstan-import-type DonneesScene from HistoireDemoSeeder
 */
class HistoireSeeder extends HistoireDemoSeeder
{
    /**
     * @var array<string, DonneesScene>
     */
    private const SCENES = [
        'debut' => [
            'titre' => '[DEMO] Début de soirée',
            'dialogue_text' => "Tu arrives à une soirée entre amis. La musique est forte, tout le monde a l'air de bien s'amuser. Un inconnu te tend un verre en souriant.",
            'image' => 'debut.jpg',
            'est_initiale' => true,
            'choix' => [
                ['text_choix' => 'Accepter le verre avec un sourire', 'suivante' => 'verre', 'issue' => null],
                ['text_choix' => 'Remercier poliment mais refuser, tu préfères rester avec tes amis', 'suivante' => 'fin', 'issue' => null],
                ['text_choix' => 'Cette soirée ne te dit rien, tu rentres directement', 'suivante' => null, 'issue' => ChoixIssue::Favorable],
            ],
        ],
        'verre' => [
            'titre' => '[DEMO] Le verre suspect',
            'dialogue_text' => "Tu regardes le verre qu'on vient de te tendre. Tu n'as pas vu comment il a été préparé, ni ce qu'il contient vraiment.",
            'image' => 'verre.jpg',
            'choix' => [
                ['text_choix' => 'Boire le verre sans poser de questions', 'suivante' => null, 'issue' => ChoixIssue::Defavorable],
                ['text_choix' => 'Refuser et demander à un ami de rester avec toi', 'suivante' => 'fin', 'issue' => null],
                ['text_choix' => 'Prévenir un agent de sécurité de la soirée', 'suivante' => null, 'issue' => ChoixIssue::Favorable],
            ],
        ],
        'fin' => [
            'titre' => '[DEMO] Fin de soirée',
            'dialogue_text' => 'Il est tard. La soirée touche à sa fin, et il faut maintenant penser à rentrer.',
            'image' => 'fin.jpg',
            'choix' => [
                ['text_choix' => 'Rentrer ensemble avec tes amis', 'suivante' => null, 'issue' => ChoixIssue::Favorable],
                ['text_choix' => "Laisser un ami rentrer seul alors qu'il a trop bu", 'suivante' => null, 'issue' => ChoixIssue::Defavorable],
                ['text_choix' => 'Appeler un taxi pour tout le monde', 'suivante' => null, 'issue' => ChoixIssue::Favorable],
            ],
        ],
    ];

    protected function ref(): string
    {
        return 'demo_soiree_qui_derape';
    }

    protected function titre(): string
    {
        return 'Une soirée qui dérape (démo)';
    }

    protected function sousThemeRef(): string
    {
        return 'alcool';
    }

    protected function dossier(): string
    {
        return 'demo-soiree-qui-derape';
    }

    protected function scenes(): array
    {
        return self::SCENES;
    }
}
