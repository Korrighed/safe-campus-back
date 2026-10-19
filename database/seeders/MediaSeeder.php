<?php

namespace Database\Seeders;

use App\Enums\MediaType;
use App\Models\Media;
use App\Models\SousTheme;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Fiches reflectives de la page /ressources/{theme}/{slug}, reprises du
 * front (app/data/themes.js). Un media par fiche, attache au sous-theme via
 * media_sous_theme. `fichier_source` : copie depuis database/seeders/data/documents/
 * vers le disque `public` au seed (storage/app/public n'est pas commite).
 * Seules les fiches dont le PDF existe sont seedees : pas d'entree sans
 * fichier (les anciens placeholders sont retires par la migration
 * 2026_10_20_100000_supprime_fiches_placeholder).
 *
 * Idempotent via `libelle` : Media n'a pas de `ref`, mais son libelle sert
 * deja de cle de recherche (voir docs/schema_bd.md#media).
 */
class MediaSeeder extends Seeder
{
    /**
     * @var array<string, list<array{titre: string, description: string, fichier_source: string}>>
     */
    private const DOCUMENTS = [
        'alcool' => [
            ['titre' => 'Fiche réflexive — Faire le point sur ma consommation', 'description' => "Un questionnaire simple pour t'aider à évaluer ta relation à l'alcool et identifier les situations à risque.", 'fichier_source' => 'fiche-alcool.pdf'],
            ['titre' => 'Dépliant — Consultations Jeunes Consommateurs (DECLIC)', 'description' => 'Entretiens confidentiels et gratuits, pour les moins de 25 ans et leur famille, sur la consommation de tabac, cannabis, alcool ou autres conduites addictives.', 'fichier_source' => 'declic-consultations-jeunes-consommateurs.pdf'],
        ],
        'tabac' => [
            ['titre' => 'Fiche réflexive — Identifier mes déclencheurs', 'description' => 'Repère les moments et émotions qui te poussent à fumer pour mieux les anticiper.', 'fichier_source' => 'fiche-cigarette.pdf'],
            ['titre' => 'Dépliant — Consultations Jeunes Consommateurs (DECLIC)', 'description' => 'Entretiens confidentiels et gratuits, pour les moins de 25 ans et leur famille, sur la consommation de tabac, cannabis, alcool ou autres conduites addictives.', 'fichier_source' => 'declic-consultations-jeunes-consommateurs.pdf'],
        ],
        'drogue' => [
            ['titre' => 'Fiche réflexive — Mon rapport aux substances', 'description' => "Un outil d'auto-évaluation pour mieux cerner ta consommation et ses effets sur ton quotidien.", 'fichier_source' => 'fiche-drogue.pdf'],
            ['titre' => 'Dépliant — Consultations Jeunes Consommateurs (DECLIC)', 'description' => 'Entretiens confidentiels et gratuits, pour les moins de 25 ans et leur famille, sur la consommation de tabac, cannabis, alcool ou autres conduites addictives.', 'fichier_source' => 'declic-consultations-jeunes-consommateurs.pdf'],
        ],
        'anxiete' => [
            ['titre' => 'Fiche pratique — Repérer et orienter une personne en détresse psychologique', 'description' => "Des repères pour ouvrir le dialogue, écouter sans juger et orienter un étudiant, un collègue ou un proche en difficulté, sans rester seul face à la situation.", 'fichier_source' => 'unc-fiche-detresse-psychologique.pdf'],
        ],
        'depression' => [
            ['titre' => 'Fiche pratique — Repérer et orienter une personne en détresse psychologique', 'description' => "Des repères pour ouvrir le dialogue, écouter sans juger et orienter un étudiant, un collègue ou un proche en difficulté, sans rester seul face à la situation.", 'fichier_source' => 'unc-fiche-detresse-psychologique.pdf'],
        ],
        'burn_out' => [
            ['titre' => 'Fiche pratique — Repérer et orienter une personne en détresse psychologique', 'description' => "Des repères pour ouvrir le dialogue, écouter sans juger et orienter un étudiant, un collègue ou un proche en difficulté, sans rester seul face à la situation.", 'fichier_source' => 'unc-fiche-detresse-psychologique.pdf'],
        ],
    ];

    public function run(): void
    {
        $sousThemeIds = SousTheme::pluck('id', 'ref');

        foreach (self::DOCUMENTS as $ref => $documents) {
            $sousThemeId = $sousThemeIds[$ref]
                ?? throw new RuntimeException("Sous-theme inconnu en base : {$ref} (MediaSeeder)");

            $pivot = [];
            foreach ($documents as $ordre => $document) {
                $chemin = $this->copierFichierSource($document['fichier_source']);

                $media = Media::updateOrCreate(
                    ['libelle' => $document['titre']],
                    [
                        'description' => $document['description'],
                        'chemin' => $chemin,
                        'type' => MediaType::Document,
                    ]
                );

                $pivot[$media->id] = ['ordre' => $ordre];
            }

            SousTheme::find($sousThemeId)->medias()->syncWithoutDetaching($pivot);
        }
    }

    /**
     * Copie idempotente vers le disque `public`. Le meme fichier source est
     * reference par plusieurs sous-themes (ex. DECLIC sous alcool/tabac/drogue) —
     * `exists()` evite de le recopier a chaque iteration.
     */
    private function copierFichierSource(string $nomFichier): string
    {
        $cheminDisque = 'medias/document/'.$nomFichier;

        if (Storage::disk('public')->exists($cheminDisque)) {
            return $cheminDisque;
        }

        $source = database_path('seeders/data/documents/'.$nomFichier);

        if (! is_file($source)) {
            throw new RuntimeException("Fichier source introuvable : {$source} (MediaSeeder)");
        }

        Storage::disk('public')->put($cheminDisque, file_get_contents($source));

        return $cheminDisque;
    }
}
