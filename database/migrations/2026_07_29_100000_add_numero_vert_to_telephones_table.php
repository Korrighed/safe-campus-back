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
        Schema::table('telephones', function (Blueprint $table) {
            $table->boolean('numero_vert')->default(false)->after('numero');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('telephones', function (Blueprint $table) {
            $table->dropColumn('numero_vert');
        });
    }
};
