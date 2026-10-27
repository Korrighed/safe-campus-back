<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Table nommee « choix » et non « choixes » : le modele Choix porte un
        // $table explicite, l'inflecteur Laravel pluralisant « Choix » en « Choixes ».
        Schema::create('choix', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scene_id')->constrained()->cascadeOnDelete();

            // Nullable : NULL signifie sortie du parcours (pas de scene suivante).
            $table->foreignId('next_scene_id')->nullable()->constrained('scenes')->nullOnDelete();

            $table->string('text_choix');

            // Nullable : reste NULL quand le choix poursuit l'histoire. Qualifie
            // la sortie sinon (favorable/defavorable) — voir docs/schema_bd.md.
            $table->enum('issue', ['favorable', 'defavorable'])->nullable();

            $table->timestamps();

            // PostgreSQL n'indexe pas la colonne referencante d'une FK.
            $table->index('scene_id');
            $table->index('next_scene_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('choix');
    }
};
