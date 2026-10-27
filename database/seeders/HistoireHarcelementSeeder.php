<?php

namespace Database\Seeders;

use App\Enums\ChoixIssue;

/**
 * Histoire de demonstration « Le groupe de TD » (sous-theme harcelement),
 * vecue en temoin : 4 scenes, 12 choix. Les scenes de messagerie sont des SVG
 * animes (CSS uniquement, sans script ni ressource externe, animations figees
 * sous prefers-reduced-motion), l'amphi et le couloir des illustrations JPG
 * generees. La personne visee n'a volontairement pas de
 * prenom, et la photo n'est jamais montree. Fonctionnement commun dans
 * HistoireDemoSeeder.
 *
 * @phpstan-import-type DonneesScene from HistoireDemoSeeder
 */
class HistoireHarcelementSeeder extends HistoireDemoSeeder
{
    /**
     * @var array<string, DonneesScene>
     */
    private const SCENES = [
        'groupe' => [
            'titre' => '[DEMO] Le groupe de TD',
            'dialogue_text' => "22h. Le groupe de TD s'agite : quelqu'un vient de poster une photo d'une fille de ta promo, prise en cours à son insu, avec une légende moqueuse. Les réactions s'enchaînent. Elle est dans le groupe, elle ne dit rien.",
            'image' => 'groupe.svg',
            'est_initiale' => true,
            'choix' => [
                ['text_choix' => 'Lui écrire en privé', 'suivante' => 'prive', 'issue' => null],
                ['text_choix' => "Ne rien dire et fermer l'appli", 'suivante' => 'amphi', 'issue' => null],
                ['text_choix' => 'Ajouter un « mdr » pour ne pas te démarquer', 'suivante' => null, 'issue' => ChoixIssue::Defavorable],
            ],
        ],
        'prive' => [
            'titre' => '[DEMO] Message privé',
            'dialogue_text' => "Tu lui écris : « ça va ? ». Les trois petits points apparaissent, disparaissent, reviennent. Enfin : « c'est rien, laisse tomber ». Puis plus rien.",
            'image' => 'prive.svg',
            'choix' => [
                ['text_choix' => "Lui proposer d'aller ensemble voir le référent harcèlement de la fac", 'suivante' => null, 'issue' => ChoixIssue::Favorable],
                ['text_choix' => "Respecter ce qu'elle dit et ne plus en parler", 'suivante' => 'amphi', 'issue' => null],
                ['text_choix' => 'Transférer la photo à tes amis pour leur montrer ce que font ces gens', 'suivante' => null, 'issue' => ChoixIssue::Defavorable],
            ],
        ],
        'amphi' => [
            'titre' => '[DEMO] En amphi',
            'dialogue_text' => "Le lendemain en amphi, des rires étouffés, quelques téléphones tournés vers elle. Elle s'assoit seule au bout d'une rangée.",
            'image' => 'amphi.jpg',
            'choix' => [
                ['text_choix' => "Aller t'asseoir à côté d'elle", 'suivante' => 'apres', 'issue' => null],
                ['text_choix' => "Écrire dans le groupe : « ce n'est pas drôle, supprimez la photo »", 'suivante' => null, 'issue' => ChoixIssue::Favorable],
                ['text_choix' => 'Rire discrètement comme les autres', 'suivante' => null, 'issue' => ChoixIssue::Defavorable],
            ],
        ],
        'apres' => [
            'titre' => '[DEMO] Après le cours',
            'dialogue_text' => "À la fin du cours, elle sort la première, très vite. Dans le couloir, tu vois qu'elle a les yeux rouges.",
            'image' => 'apres.jpg',
            'choix' => [
                ['text_choix' => "Lui dire que tu as vu, que ce n'est pas normal, et proposer de l'accompagner à la vie étudiante", 'suivante' => null, 'issue' => ChoixIssue::Favorable],
                ['text_choix' => 'Avec son accord, faire des captures du groupe et les transmettre au référent', 'suivante' => null, 'issue' => ChoixIssue::Favorable],
                ['text_choix' => 'Te dire que ce ne sont pas tes affaires', 'suivante' => null, 'issue' => ChoixIssue::Defavorable],
            ],
        ],
    ];

    protected function ref(): string
    {
        return 'demo_groupe_de_td';
    }

    protected function titre(): string
    {
        return 'Le groupe de TD (démo)';
    }

    protected function sousThemeRef(): string
    {
        return 'harcelement';
    }

    protected function dossier(): string
    {
        return 'demo-groupe-de-td';
    }

    protected function scenes(): array
    {
        return self::SCENES;
    }
}
