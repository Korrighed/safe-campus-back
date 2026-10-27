<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Schema::table()->enum() ne sait qu'ajouter une colonne, pas alterer un
 * CHECK existant sur PostgreSQL : ALTER TABLE en brut necessaire pour
 * autoriser 'visiteur' dans users.role.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE users DROP CONSTRAINT users_role_check');
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role::text = ANY (ARRAY['webmaster'::character varying, 'redacteur'::character varying, 'visiteur'::character varying]::text[]))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE users DROP CONSTRAINT users_role_check');
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role::text = ANY (ARRAY['webmaster'::character varying, 'redacteur'::character varying]::text[]))");
    }
};
