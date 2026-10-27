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
        Schema::create('media_theme', function (Blueprint $table) {
            // Table cible explicite : constrained() deduirait « media » de la
            // colonne media_id, or la table s'appelle « medias ».
            $table->foreignId('media_id')->constrained('medias')->cascadeOnDelete();

            $table->foreignId('theme_id')->constrained()->cascadeOnDelete();

            $table->primary(['media_id', 'theme_id']);

            // La PK composite couvre deja les acces par media_id (prefixe gauche).
            $table->index('theme_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_theme');
    }
};
