<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ordre est porte par la liaison, pas par la scene : une scene partagee peut
 * avoir un rang different dans chaque histoire (comme est_initiale).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('histoire_scene', function (Blueprint $table) {
            $table->unsignedInteger('ordre')->default(0);
        });

        // Rattachements existants : numerotes par histoire, scene initiale
        // d'abord puis par id, sinon ils resteraient tous a 0.
        DB::statement(<<<'SQL'
            UPDATE histoire_scene hs
            SET ordre = r.rang
            FROM (
                SELECT histoire_id, scene_id,
                       ROW_NUMBER() OVER (PARTITION BY histoire_id ORDER BY est_initiale DESC, scene_id) AS rang
                FROM histoire_scene
            ) r
            WHERE hs.histoire_id = r.histoire_id AND hs.scene_id = r.scene_id
        SQL);
    }

    public function down(): void
    {
        Schema::table('histoire_scene', function (Blueprint $table) {
            $table->dropColumn('ordre');
        });
    }
};
