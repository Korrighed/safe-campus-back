<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sous_themes', function (Blueprint $table) {
            // Ordre editorial, porte la numerotation affichee ("N°1", "N°2").
            $table->unsignedInteger('ordre')->default(0);
            $table->text('intro_ressources')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('sous_themes', function (Blueprint $table) {
            $table->dropColumn(['ordre', 'intro_ressources']);
        });
    }
};
