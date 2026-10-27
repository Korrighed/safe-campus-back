<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Compte redacteur de demonstration, sur le meme principe que le webmaster de
 * demo (voir migration seed_webmaster_demonstration) : identifiants pilotes
 * par REDACTEUR_DEMO_EMAIL / REDACTEUR_DEMO_PASSWORD (.env.example, lus via
 * config/redacteur_demo.php), jamais en production. Sert de proprietaire aux
 * histoires de demo (HistoireSeeder), doit donc s'executer avant.
 */
class RedacteurDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            return;
        }

        User::firstOrCreate(
            ['email' => config('redacteur_demo.email')],
            [
                'nom' => 'Test',
                'prenom' => 'Rédacteur',
                'password' => config('redacteur_demo.password'),
                'role' => UserRole::Redacteur,
            ],
        );
    }
}
