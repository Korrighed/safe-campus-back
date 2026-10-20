<?php

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Premier compte admin, provisionne au migrate dans tous les environnements.
 * Identifiants pilotes par ADMIN_EMAIL / ADMIN_PASSWORD, lus via
 * config/admin.php : valeurs de dev dans .env.example, vraies valeurs dans
 * la configuration de l'hebergement en production. Le mot de passe ne sert qu'a la creation — une fois
 * le compte cree, la migration ne fait plus rien.
 *
 * Insertion via DB::table, pas via le modele/factory : une migration ne doit
 * pas dependre du schema courant du modele.
 */
return new class extends Migration
{
    public function up(): void
    {
        $email = mb_strtolower(trim((string) config('admin.email')));

        if (DB::table('users')->whereRaw('lower(email) = ?', [$email])->exists()) {
            return;
        }

        $password = config('admin.password');

        if (empty($password)) {
            throw new RuntimeException('ADMIN_PASSWORD manquant : a definir dans .env avant migrate.');
        }

        // En production, meme politique que l'inscription (Password::defaults()).
        if (app()->environment('production')
            && Validator::make(['password' => $password], ['password' => Password::default()])->fails()) {
            throw new RuntimeException('ADMIN_PASSWORD trop faible : 14 caracteres min., majuscule, minuscule, caractere special.');
        }

        DB::table('users')->insert([
            'nom' => 'Administrateur',
            'email' => $email,
            'email_verified_at' => now(),
            'password' => Hash::make($password),
            'role' => UserRole::Admin->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('users')->whereRaw('lower(email) = ?', [mb_strtolower((string) config('admin.email'))])->delete();
    }
};
