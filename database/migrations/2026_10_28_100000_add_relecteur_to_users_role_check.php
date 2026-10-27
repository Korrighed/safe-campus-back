<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * CHECK existant sur PostgreSQL : ALTER TABLE en brut necessaire pour
 * autoriser 'relecteur' dans users.role.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE users DROP CONSTRAINT users_role_check');
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role::text = ANY (ARRAY['webmaster'::character varying, 'redacteur'::character varying, 'relecteur'::character varying, 'visiteur'::character varying, 'admin'::character varying]::text[]))");
    }

    public function down(): void
    {
        // Les relecteurs nommes depuis le panel repassent en attente : la
        // contrainte d'origine refuserait la valeur 'relecteur'.
        DB::table('users')->where('role', 'relecteur')->update(['role' => null]);

        DB::statement('ALTER TABLE users DROP CONSTRAINT users_role_check');
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role::text = ANY (ARRAY['webmaster'::character varying, 'redacteur'::character varying, 'visiteur'::character varying, 'admin'::character varying]::text[]))");
    }
};
