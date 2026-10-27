<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Retire les fiches reflectives « placeholder » seedees par MediaSeeder sans
 * fichier (chemin vide) : le front les affichait avec un bouton desactive.
 * MediaSeeder ne les cree plus, mais ne supprime rien (updateOrCreate) — d'ou
 * cette migration pour les bases deja seedees.
 *
 * Ciblage par libelle exact ET chemin vide : une fiche a laquelle un fichier
 * aurait ete ajoute depuis l'admin est conservee. Les pivots media_sous_theme
 * et media_theme partent en cascade.
 */
return new class extends Migration
{
    private const LIBELLES = [
        'Fiche pratique — Réduire sans se priver',
        'Guide — Les premières semaines sans tabac',
        'Fiche info — Réduction des risques',
        'Fiche réflexive — Cartographier mon anxiété',
        "Fiche pratique — Exercices de respiration et d'ancrage",
        'Fiche réflexive — Reconnaître les signaux',
        'Fiche pratique — Petits pas au quotidien',
        'Fiche réflexive — Évaluer ma charge mentale',
        'Fiche pratique — Réorganiser son temps sans culpabiliser',
        'Fiche réflexive — Reconnaître les violences sexistes',
        'Fiche pratique — Réagir et se faire accompagner',
        'Fiche réflexive — Comprendre le consentement',
        'Fiche pratique — Les démarches après une agression',
        'Fiche réflexive — Documenter une situation de harcèlement',
        'Fiche pratique — Agir en tant que témoin',
    ];

    public function up(): void
    {
        DB::table('medias')
            ->whereIn('libelle', self::LIBELLES)
            ->where('chemin', '')
            ->delete();
    }

    /**
     * Irreversible : les placeholders n'ont pas vocation a revenir.
     */
    public function down(): void {}
};
