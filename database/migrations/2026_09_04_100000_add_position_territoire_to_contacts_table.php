<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Distingue "position pas encore geocodee" (latitude/longitude null) de
 * "structure couvrant tout le territoire, pas de point cartographiable"
 * (position_territoire = true, latitude/longitude toujours null).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->boolean('position_territoire')->default(false)->after('longitude');
        });

        DB::statement(<<<'SQL'
            ALTER TABLE contacts
            ADD CONSTRAINT contacts_position_territoire_sans_coordonnees
            CHECK (NOT (position_territoire AND (latitude IS NOT NULL OR longitude IS NOT NULL)))
        SQL);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE contacts DROP CONSTRAINT contacts_position_territoire_sans_coordonnees');

        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn('position_territoire');
        });
    }
};
