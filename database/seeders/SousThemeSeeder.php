<?php

namespace Database\Seeders;

use App\Models\SousTheme;
use App\Models\Theme;
use Illuminate\Database\Seeder;

/**
 * Taxonomie + contenu editorial (resume/article/intro_ressources), repris du
 * front (app/data/themes.js), voir docs/textes-front.md. Refs alignees sur
 * la taxonomie 2026-07-31 : `drogue` (front: drogues), `burn_out` (front:
 * burn-out) — le front consomme le ref renvoye par l'API, pas ses propres
 * slugs.
 */
class SousThemeSeeder extends Seeder
{
    /**
     * @var list<array{ref: string, libelle: string, theme_ref: string, ordre: int, resume: string, article: string, intro_ressources: string}>
     */
    private const SOUS_THEMES = [
        [
            'ref' => 'alcool',
            'libelle' => 'Alcool',
            'theme_ref' => 'addictions',
            'ordre' => 0,
            'resume' => "Tu as dit que c'était ton dernier verre hier.",
            'article' => "L'alcool est la substance psychoactive la plus consommée en France. Une consommation excessive peut entraîner des dépendances physiques et psychologiques, affecter les relations sociales et nuire à la santé sur le long terme. On parle de consommation à risque dès 2 verres par jour pour une femme, 3 pour un homme. La dépendance s'installe progressivement, souvent sans qu'on s'en rende compte. Des solutions existent pour t'accompagner.",
            'intro_ressources' => "Comprendre sa consommation est la première étape pour reprendre le contrôle. Cette page rassemble des repères simples et des outils pratiques pour t'aider à faire le point, à ton rythme et sans jugement — que tu cherches à réduire ta consommation, à en parler à quelqu'un, ou juste à mieux comprendre ce que tu traverses.",
        ],
        [
            'ref' => 'tabac',
            'libelle' => 'Tabac',
            'theme_ref' => 'addictions',
            'ordre' => 1,
            'resume' => 'La clope est devenue un réflexe plus qu’un plaisir.',
            'article' => "Le tabagisme est la première cause de mortalité évitable en France. La nicotine crée une dépendance forte en agissant directement sur les circuits de récompense du cerveau. Les symptômes de manque (irritabilité, anxiété, troubles du sommeil) disparaissent généralement en quelques semaines avec un accompagnement adapté. Chaque tentative d'arrêt rapproche de la réussite définitive — il faut en moyenne 5 à 7 tentatives avant d'arrêter durablement.",
            'intro_ressources' => "Arrêter de fumer est un parcours, pas un événement unique. Ces ressources t'accompagnent pour préparer ton arrêt, gérer les envies et tenir sur la durée, avec des méthodes qui ont fait leurs preuves.",
        ],
        [
            'ref' => 'drogue',
            'libelle' => 'Drogue',
            'theme_ref' => 'addictions',
            'ordre' => 2,
            'resume' => "Tu ne sais plus vraiment dire non quand on t'en propose.",
            'article' => "Les drogues illicites (cannabis, cocaïne, MDMA, héroïne…) et certains médicaments détournés peuvent entraîner des addictions sévères aux conséquences physiques, psychologiques et sociales graves. Le cannabis, souvent perçu comme anodin, peut provoquer une dépendance chez 1 usager sur 10, chiffre qui monte à 1 sur 6 pour ceux qui commencent à l'adolescence. Un accompagnement professionnel confidentiel est disponible sans jugement.",
            'intro_ressources' => "Que ta consommation soit occasionnelle ou plus régulière, il est utile de connaître les risques réels et les ressources disponibles. Cette page propose des repères neutres, sans jugement, pour t'aider à faire des choix informés.",
        ],
        [
            'ref' => 'anxiete',
            'libelle' => 'Anxiété',
            'theme_ref' => 'sante_mentale',
            'ordre' => 0,
            'resume' => 'Certains matins, aller en cours te serre déjà la gorge.',
            'article' => "L'anxiété est une réaction normale face au stress, mais elle devient problématique quand elle est disproportionnée, persistante et qu'elle interfère avec la vie quotidienne. En période d'examens ou lors de transitions de vie, elle peut se manifester par des palpitations, des tensions musculaires, des troubles du sommeil ou des pensées envahissantes. Des techniques concrètes (respiration, TCC, pleine conscience) et un soutien psychologique peuvent faire une vraie différence.",
            'intro_ressources' => "L'anxiété se gère mieux quand on comprend ses mécanismes. Retrouve ici des techniques simples à pratiquer au quotidien et des pistes pour savoir quand consulter.",
        ],
        [
            'ref' => 'depression',
            'libelle' => 'Dépression',
            'theme_ref' => 'sante_mentale',
            'ordre' => 1,
            'resume' => 'Plus rien ne te fait vraiment plaisir depuis des semaines.',
            'article' => "La dépression est une maladie réelle, pas une faiblesse de caractère. Elle se manifeste par une tristesse profonde et durable, une perte d'intérêt ou de plaisir, une fatigue intense, des troubles du sommeil et de l'appétit, et parfois des pensées sombres. Elle touche environ 1 étudiant sur 5 à un moment donné de son parcours. Sans prise en charge, elle peut s'aggraver — mais avec un accompagnement adapté, la guérison est possible.",
            'intro_ressources' => "La dépression se soigne, et en parler est déjà un premier pas. Ces ressources t'aident à reconnaître les signaux et à identifier les appuis autour de toi.",
        ],
        [
            'ref' => 'burn_out',
            'libelle' => 'Burn-out',
            'theme_ref' => 'sante_mentale',
            'ordre' => 2,
            'resume' => "Même le week-end ne suffit plus à te redonner de l'énergie.",
            'article' => "L'épuisement académique (burn-out étudiant) est un syndrome d'épuisement physique et émotionnel lié à une surcharge de travail prolongée. Il diffère de la fatigue passagère : il s'installe progressivement et ne disparaît pas avec quelques nuits de sommeil. Les signaux d'alerte : perte totale de motivation, sentiment d'inefficacité, cynisme vis-à-vis des études, oublis fréquents, isolement. Reconnaître ces signes tôt permet d'éviter l'effondrement complet.",
            'intro_ressources' => "L'épuisement académique s'installe progressivement — apprendre à repérer les signaux tôt permet d'éviter l'effondrement. Ces ressources t'aident à faire le point sur ta charge et à identifier des leviers concrets.",
        ],
        [
            'ref' => 'violences_sexistes',
            'libelle' => 'Violences sexistes',
            'theme_ref' => 'vss',
            'ordre' => 0,
            'resume' => "On t'a encore dit que tu exagérais. Toi, tu sais.",
            'article' => "Les violences sexistes désignent tout comportement, propos ou pratique fondé sur le genre qui porte atteinte à la dignité d'une personne. Cela inclut les remarques dégradantes, les blagues humiliantes, la remise en question des compétences basée sur le genre, ou encore les comportements intimidants. Ces violences peuvent survenir en cours, en soirée étudiante, en ligne ou dans la vie quotidienne. Elles sont illégales et des recours existent.",
            'intro_ressources' => "Reconnaître une situation de violence sexiste est parfois difficile, surtout quand elle est banalisée. Ces ressources t'aident à mettre des mots sur ce que tu vis et à connaître tes droits.",
        ],
        [
            'ref' => 'violences_sexuelles',
            'libelle' => 'Violences sexuelles',
            'theme_ref' => 'vss',
            'ordre' => 1,
            'resume' => 'Tu te repasses la soirée en boucle, encore et encore.',
            'article' => "Toute relation ou acte sexuel sans consentement libre et éclairé est une violence. Le viol, l'agression sexuelle et le harcèlement sexuel sont des infractions pénales. Le consentement doit être explicite et peut être retiré à tout moment. La soumission chimique (droguer quelqu'un à son insu) est également un crime. Si tu as vécu une violence sexuelle, tu n'es pas responsable. Des professionnels formés sont disponibles pour t'écouter, sans te juger.",
            'intro_ressources' => "Après une violence sexuelle, il est normal de ressentir de la confusion ou de la culpabilité — tu n'es pas responsable. Ces ressources expliquent tes droits et les démarches possibles, à ton rythme.",
        ],
        [
            'ref' => 'harcelement',
            'libelle' => 'Harcèlement',
            'theme_ref' => 'vss',
            'ordre' => 2,
            'resume' => "Les messages ne s'arrêtent pas, même sans réponse.",
            'article' => "Le harcèlement moral est défini par sa répétition : ce sont des agissements répétés qui dégradent les conditions de vie ou de travail d'une personne. Le cyberharcèlement (via réseaux sociaux, SMS, messageries) touche de plus en plus d'étudiants et peut avoir des conséquences sévères sur la santé mentale. Que tu sois victime ou témoin, des recours concrets existent. Documenter les faits (captures d'écran, dates, témoins) facilite les démarches.",
            'intro_ressources' => "Le harcèlement se caractérise par la répétition — que tu sois victime ou témoin, il existe des moyens concrets d'agir. Ces ressources t'aident à documenter les faits et à connaître tes recours.",
        ],
    ];

    public function run(): void
    {
        $themeIds = Theme::pluck('id', 'ref');

        foreach (self::SOUS_THEMES as $sousTheme) {
            SousTheme::updateOrCreate(
                ['ref' => $sousTheme['ref']],
                [
                    'libelle' => $sousTheme['libelle'],
                    'theme_id' => $themeIds[$sousTheme['theme_ref']],
                    'ordre' => $sousTheme['ordre'],
                    'resume' => $sousTheme['resume'],
                    'article' => $sousTheme['article'],
                    'intro_ressources' => $sousTheme['intro_ressources'],
                ]
            );
        }
    }
}
