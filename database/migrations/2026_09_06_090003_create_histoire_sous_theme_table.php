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
        Schema::create('histoire_sous_theme', function (Blueprint $table) {
            $table->foreignId('histoire_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sous_theme_id')->constrained()->cascadeOnDelete();

            $table->primary(['histoire_id', 'sous_theme_id']);

            // La PK composite couvre deja les acces par histoire_id (prefixe gauche).
            $table->index('sous_theme_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('histoire_sous_theme');
    }
};
