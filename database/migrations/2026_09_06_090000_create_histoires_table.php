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
        Schema::create('histoires', function (Blueprint $table) {
            $table->id();
            $table->string('ref')->unique();
            $table->string('titre');
            $table->enum('etat', ['brouillon', 'relecture', 'valide', 'publie'])->default('brouillon');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            // PostgreSQL n'indexe pas la colonne referencante d'une FK.
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('histoires');
    }
};
