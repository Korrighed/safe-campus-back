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
 * Socle des histoires de demonstration : chaque histoire ne declare que ses
 * donnees (ref, titre, sous-theme, dossier d'images, scenes).
 *
 * Idempotent : l'histoire est cherchee par `ref`, une scene par (histoire,
 * titre), un choix par (scene, texte), un media par chemin. Rejouer le seeder
 * met a jour sans dupliquer. Changer le nom d'un fichier image (ex. .svg en
 * .jpg) cree un nouveau media : l'ancien reste en base, a supprimer a la main.
 * Jamais en production : c'est du contenu de demo.
 * Le compte redacteur proprietaire est cree par RedacteurDemoSeeder, qui doit
 * s'executer avant.
 *
 * @phpstan-type DonneesScene array{titre: string, dialogue_text: string, image: string, est_initiale?: bool, choix: list<array{text_choix: string, suivante: ?string, issue: ?ChoixIssue}>}
 */
abstract class HistoireDemoSeeder extends Seeder
{
    abstract protected function ref(): string;

    abstract protected function titre(): string;

    abstract protected function sousThemeRef(): string;

    /**
     * Sous-dossier des images, a la fois sous database/seeders/data/images/
     * (source commitee) et sous medias/image/ du disque `public`.
     */
    abstract protected function dossier(): string;

    /**
     * Cle = identifiant interne au seeder, sert a relier les choix aux scenes
     * suivantes. L'ordre du tableau est l'ordre des scenes dans l'histoire.
     *
     * @return array<string, DonneesScene>
     */
    abstract protected function scenes(): array;

    public function run(): void
    {
        if (app()->environment('production')) {
            return;
        }

        $redacteur = User::where('email', config('redacteur_demo.email'))->firstOrFail();

        $histoire = Histoire::updateOrCreate(
            ['ref' => $this->ref()],
            [
                'titre' => $this->titre(),
                'etat' => EtatHistoire::Publie,
                'user_id' => $redacteur->id,
            ],
        );

        $sousTheme = SousTheme::where('ref', $this->sousThemeRef())->first();
        $histoire->sousThemes()->sync($sousTheme ? [$sousTheme->id] : []);

        // Premier passage : les scenes et leur rattachement. Les choix viennent
        // apres, une scene suivante devant exister pour etre referencee.
        $scenes = [];
        $rang = 1;

        foreach ($this->scenes() as $cle => $donnees) {
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

        foreach ($this->scenes() as $cle => $donnees) {
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
     * source change : retoucher une image puis rejouer le seeder suffit.
     */
    private function copierFichierSource(string $cle, string $nomFichier): string
    {
        $cheminDisque = 'medias/image/'.$this->dossier().'/'.$nomFichier;
        $source = database_path('seeders/data/images/'.$this->dossier().'/'.$nomFichier);

        if (! is_file($source)) {
            throw new RuntimeException("Fichier source introuvable : {$source} (".static::class.", scene {$cle})");
        }

        $contenu = file_get_contents($source);
        $disque = Storage::disk('public');

        if (! $disque->exists($cheminDisque) || $disque->get($cheminDisque) !== $contenu) {
            $disque->put($cheminDisque, $contenu);
        }

        return $cheminDisque;
    }
}
