<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // L'ancien `name` devient `nom` : les comptes existants gardent leur
            // libelle complet dans `nom`, `prenom` reste vide.
            $table->renameColumn('name', 'nom');
        });

        Schema::table('users', function (Blueprint $table) {
            // Nullable : obligatoires a l'inscription (formulaire Filament), mais
            // les comptes crees avant cette migration n'en ont pas.
            $table->string('prenom')->nullable();
            // Format E.164 (+687XXXXXX), normalise par la page d'inscription.
            $table->string('telephone', 20)->nullable();
        });

        // Filet de securite sous la validation de l'inscription : l'index
        // unique d'origine distingue JEAN@test.nc de jean@test.nc.
        DB::statement('CREATE UNIQUE INDEX users_email_lower_unique ON users (lower(email))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS users_email_lower_unique');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['prenom', 'telephone']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('nom', 'name');
        });
    }
};
