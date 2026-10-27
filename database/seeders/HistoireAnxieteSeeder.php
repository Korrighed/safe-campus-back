<?php

namespace Database\Seeders;

use App\Enums\ChoixIssue;
use App\Enums\EtatHistoire;
use App\Enums\MediaType;
use App\Models\Choix;
use App\Models\Histoire;
use App\Models\Media;
use App\Models\Scene;
use App\Models\SousTheme;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Histoire de demonstration « La veille du partiel » (sous-theme anxiete) :
 * 4 scenes, 12 choix, illustrees par des SVG animes (CSS uniquement, sans
 * script ni ressource externe, animations figees sous prefers-reduced-motion).
 *
 * Meme principe que HistoireSeeder : idempotent (histoire par `ref`, scene par
 * (histoire, titre), choix par (scene, texte)), jamais en production, le
 * compte redacteur de RedacteurDemoSeeder doit exister.
 */
class HistoireAnxieteSeeder extends Seeder
{
    private const REF = 'demo_veille_du_partiel';

    private const SOUS_THEME_REF = 'anxiete';

    private const DOSSIER = 'demo-veille-du-partiel';

    /**
     * Cle = identifiant interne au seeder, sert a relier les choix aux scenes
     * suivantes. L'ordre du tableau est l'ordre des scenes dans l'histoire.
     *
     * @var array<string, array{titre: string, dialogue_text: string, image: string, est_initiale?: bool, choix: list<array{text_choix: string, suivante: ?string, issue: ?ChoixIssue}>}>
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

    public function run(): void
    {
        if (app()->environment('production')) {
            return;
        }

        $redacteur = User::where('email', config('redacteur_demo.email'))->firstOrFail();

        $histoire = Histoire::updateOrCreate(
            ['ref' => self::REF],
            [
                'titre' => 'La veille du partiel (démo)',
                'etat' => EtatHistoire::Publie,
                'user_id' => $redacteur->id,
            ],
        );

        $sousTheme = SousTheme::where('ref', self::SOUS_THEME_REF)->first();
        $histoire->sousThemes()->sync($sousTheme ? [$sousTheme->id] : []);

        // Premier passage : les scenes et leur rattachement. Les choix viennent
        // apres, une scene suivante devant exister pour etre referencee.
        $scenes = [];
        $rang = 1;

        foreach (self::SCENES as $cle => $donnees) {
            $scene = $histoire->scenes()->where('titre', $donnees['titre'])->first()
                ?? Scene::create(['titre' => $donnees['titre'], 'dialogue_text' => $donnees['dialogue_text']]);

            $media = $this->trouverOuCreerImage($cle, $donnees['titre'], $donnees['image']);

            $scene->update(['dialogue_text' => $donnees['dialogue_text'], 'media_id' => $media->id]);

            $histoire->scenes()->syncWithoutDetaching([
                $scene->id => [
                    'est_initiale' => $donnees['est_initiale'] ?? false,
                    'ordre' => $rang++,
                ],
            ]);

            $scenes[$cle] = $scene;
        }

        foreach (self::SCENES as $cle => $donnees) {
            foreach ($donnees['choix'] as $choix) {
                Choix::updateOrCreate(
                    ['scene_id' => $scenes[$cle]->id, 'text_choix' => $choix['text_choix']],
                    [
                        'next_scene_id' => $choix['suivante'] ? $scenes[$choix['suivante']]->id : null,
                        'issue' => $choix['issue'],
                    ],
                );
            }
        }
    }

    /**
     * Idempotent via le chemin plutot que le libelle : des libelles courts
     * comme « Une pause » pourraient deja exister cote back-office.
     */
    private function trouverOuCreerImage(string $cle, string $titreScene, string $nomFichier): Media
    {
        $chemin = $this->copierFichierSource($cle, $nomFichier);

        return Media::updateOrCreate(
            ['chemin' => $chemin],
            [
                'libelle' => str_replace('[DEMO] ', '', $titreScene),
                'description' => null,
                'type' => MediaType::Image,
            ],
        );
    }

    /**
     * Copie vers le disque `public` (storage/app/public n'est pas commite),
     * depuis database/seeders/data/images/. Le fichier est recopie des que la
     * source change : retoucher un SVG puis rejouer le seeder suffit.
     */
    private function copierFichierSource(string $cle, string $nomFichier): string
    {
        $cheminDisque = 'medias/image/'.self::DOSSIER.'/'.$nomFichier;
        $source = database_path('seeders/data/images/'.self::DOSSIER.'/'.$nomFichier);

        if (! is_file($source)) {
            throw new RuntimeException("Fichier source introuvable : {$source} (HistoireAnxieteSeeder, scene {$cle})");
        }

        $contenu = file_get_contents($source);
        $disque = Storage::disk('public');

        if (! $disque->exists($cheminDisque) || $disque->get($cheminDisque) !== $contenu) {
            $disque->put($cheminDisque, $contenu);
        }

        return $cheminDisque;
    }
}
