<?php

namespace App\Services;

use App\Models\Histoire;
use App\Models\Scene;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Contrôles informatifs : rien n'est bloqué.
 */
class VerificationHistoireService
{
    /**
     * @return list<array{cle: string, libelle: string, ok: bool, details: list<string>}>
     */
    public function controler(Histoire $histoire): array
    {
        /** @var Collection<int, Scene> $scenes */
        $scenes = $histoire->scenes()->with('choix')->get();
        $sceneIds = $scenes->modelKeys();
        $atteignables = $this->scenesAtteignables($histoire);

        $sansChoix = $scenes->filter(fn (Scene $scene) => $scene->choix->isEmpty());
        $sansMiseEnSituation = $scenes->filter(fn (Scene $scene) => blank($scene->dialogue_text));
        $injoignables = $scenes->reject(fn (Scene $scene) => in_array($scene->id, $atteignables, true));

        $choixIncomplets = [];
        $choixHorsHistoire = [];
        $aUneFin = false;

        foreach ($scenes as $scene) {
            foreach ($scene->choix as $choix) {
                if ($choix->next_scene_id === null && $choix->issue === null) {
                    $choixIncomplets[] = "« {$choix->text_choix} » (scène {$scene->titre})";
                }

                if ($choix->next_scene_id !== null && ! in_array($choix->next_scene_id, $sceneIds, true)) {
                    $choixHorsHistoire[] = "« {$choix->text_choix} » (scène {$scene->titre})";
                }

                if ($choix->issue !== null && in_array($scene->id, $atteignables, true)) {
                    $aUneFin = true;
                }
            }
        }

        return [
            [
                'cle' => 'depart',
                'libelle' => 'L\'histoire a une scène de départ',
                'ok' => $scenes->contains(fn (Scene $scene) => $scene->pivot->est_initiale),
                'details' => [],
            ],
            [
                'cle' => 'atteignables',
                'libelle' => 'Toutes les scènes sont atteignables depuis le départ',
                'ok' => $injoignables->isEmpty(),
                'details' => $injoignables->pluck('titre')->values()->all(),
            ],
            [
                'cle' => 'mise_en_situation',
                'libelle' => 'Chaque scène a une mise en situation',
                'ok' => $sansMiseEnSituation->isEmpty(),
                'details' => $sansMiseEnSituation->pluck('titre')->values()->all(),
            ],
            [
                'cle' => 'choix_par_scene',
                'libelle' => 'Chaque scène propose au moins un choix',
                'ok' => $sansChoix->isEmpty(),
                'details' => $sansChoix->pluck('titre')->values()->all(),
            ],
            [
                'cle' => 'choix_complets',
                'libelle' => 'Chaque choix mène à une scène ou à une fin',
                'ok' => $choixIncomplets === [],
                'details' => $choixIncomplets,
            ],
            [
                'cle' => 'choix_internes',
                'libelle' => 'Les choix ne mènent qu\'à des scènes de cette histoire',
                'ok' => $choixHorsHistoire === [],
                'details' => $choixHorsHistoire,
            ],
            [
                'cle' => 'fin',
                'libelle' => 'Le parcours a au moins une fin',
                'ok' => $aUneFin,
                'details' => [],
            ],
            [
                'cle' => 'sous_themes',
                'libelle' => 'L\'histoire est rattachée à au moins un sous-thème',
                'ok' => $histoire->sousThemes()->exists(),
                'details' => [],
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public function problemes(Histoire $histoire): array
    {
        return collect($this->controler($histoire))
            ->reject(fn (array $controle) => $controle['ok'])
            ->pluck('libelle')
            ->values()
            ->all();
    }

    /**
     * Scenes accessibles en suivant les choix depuis la scene initiale, sans
     * sortir des scenes liees a l'histoire.
     *
     * @return list<int>
     */
    public function scenesAtteignables(Histoire $histoire): array
    {
        $lignes = DB::select(<<<'SQL'
            WITH RECURSIVE atteignables AS (
                SELECT hs.scene_id
                FROM histoire_scene hs
                WHERE hs.histoire_id = ? AND hs.est_initiale
                UNION
                SELECT c.next_scene_id
                FROM choix c
                JOIN atteignables a ON a.scene_id = c.scene_id
                JOIN histoire_scene hs ON hs.scene_id = c.next_scene_id AND hs.histoire_id = ?
            )
            SELECT scene_id FROM atteignables
        SQL, [$histoire->id, $histoire->id]);

        return array_map(fn (object $ligne) => (int) $ligne->scene_id, $lignes);
    }
}
