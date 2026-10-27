<?php

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Compte webmaster de demonstration pour le dev local, provisionne au
 * migrate pour que chaque dev l'ait sans etape manuelle — WEBMASTER_DEMO_EMAIL
 * / WEBMASTER_DEMO_PASSWORD dans .env.example (identifiants bidon, commites
 * volontairement), lus via config/webmaster_demo.php. Ne s'execute jamais en
 * production.
 *
 * Insertion via DB::table, pas via le modele/factory : a ce stade du migrate,
 * la table a encore la colonne `name` (renommee en `nom` plus tard).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (app()->environment('production')) {
            return;
        }

        $email = config('webmaster_demo.email');

        if (DB::table('users')->where('email', $email)->exists()) {
            return;
        }

        DB::table('users')->insert([
            'name' => 'Webmaster (demo)',
            'email' => $email,
            'email_verified_at' => now(),
            'password' => Hash::make(config('webmaster_demo.password')),
            'role' => UserRole::Webmaster->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('users')->where('email', config('webmaster_demo.email'))->delete();
    }
};
