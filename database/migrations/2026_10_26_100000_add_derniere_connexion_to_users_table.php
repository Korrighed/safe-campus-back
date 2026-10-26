<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Nullable : un compte qui ne s'est jamais connecte (inscription
            // en attente) est juge sur created_at, voir SuppressionCompteService.
            $table->timestamp('derniere_connexion_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('derniere_connexion_at');
        });
    }
};
