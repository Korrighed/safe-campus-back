<?php

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Compte visiteur unique, provisionne au migrate dans tous les
 * environnements (y compris production) — c'est le compte que le front
 * utilise pour authentifier l'acces public au site. Identifiants pilotes
 * par VISITEUR_EMAIL / VISITEUR_PASSWORD, lus via config/visiteur.php.
 *
 * Insertion via DB::table, pas via le modele/factory : a ce stade du migrate,
 * la table a encore la colonne `name` (renommee en `nom` plus tard).
 */
return new class extends Migration
{
    public function up(): void
    {
        $email = config('visiteur.email');

        if (DB::table('users')->where('email', $email)->exists()) {
            return;
        }

        $password = config('visiteur.password');

        if (empty($password)) {
            throw new RuntimeException('VISITEUR_PASSWORD manquant : a definir dans .env avant migrate (min. 14 caracteres).');
        }

        DB::table('users')->insert([
            'name' => 'Visiteur',
            'email' => $email,
            'email_verified_at' => now(),
            'password' => Hash::make($password),
            'role' => UserRole::Visiteur->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('users')->where('email', config('visiteur.email'))->delete();
    }
};
