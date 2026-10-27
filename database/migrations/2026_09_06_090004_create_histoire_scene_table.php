<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Est_initiale est porte par la liaison, pas par la scene : une scene partagee
 * peut etre le point d'entree d'une histoire et une etape intermediaire d'une
 * autre. Voir docs/schema_bd.md § Scene et partage entre histoires.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('histoire_scene', function (Blueprint $table) {
            $table->foreignId('histoire_id')->constrained()->cascadeOnDelete();
            $table->foreignId('scene_id')->constrained()->cascadeOnDelete();
            $table->boolean('est_initiale')->default(false);

            $table->primary(['histoire_id', 'scene_id']);

            // La PK composite couvre deja les acces par histoire_id (prefixe gauche).
            $table->index('scene_id');
        });

        // Une seule scene initiale par histoire. Pas d'equivalent fluent Blueprint
        // pour un index unique partiel (WHERE est_initiale) : SQL brut necessaire.
        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX histoire_scene_initiale_unique
                ON histoire_scene (histoire_id) WHERE est_initiale
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS histoire_scene_initiale_unique');

        Schema::dropIfExists('histoire_scene');
    }
};
