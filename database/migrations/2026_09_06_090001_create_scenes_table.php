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
        Schema::create('scenes', function (Blueprint $table) {
            $table->id();
            $table->string('titre');
            $table->text('dialogue_text');

            // Nullable : exception qui permet a une scene de faire bifurquer le
            // recit vers une autre thematique que celle de l'histoire (alcool ->
            // cannabis). Ne fait pas foi sur la thematique de l'histoire elle-meme.
            $table->foreignId('sous_theme_id')->nullable()->constrained()->nullOnDelete();

            // Nullable : image ou fond de la scene, optionnel.
            // Table cible explicite : constrained() deduirait « media » de la
            // colonne media_id, or la table s'appelle « medias ».
            $table->foreignId('media_id')->nullable()->constrained('medias')->nullOnDelete();

            $table->timestamps();

            // PostgreSQL n'indexe pas la colonne referencante d'une FK.
            $table->index('sous_theme_id');
            $table->index('media_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scenes');
    }
};
