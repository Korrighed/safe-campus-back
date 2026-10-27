<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        if (! User::where('email', 'test@example.com')->exists()) {
            User::factory()->create([
                'nom' => 'User',
                'prenom' => 'Test',
                'email' => 'test@example.com',
            ]);
        }

        // Theme/SousTheme : taxonomie + contenu editorial, seedee par la
        // migration 2026_07_31_120000_seed_taxonomie_themes_sous_themes —
        // pas ici. MediaSeeder en depend (attache les documents aux
        // sous-themes), doit donc s'executer apres.
        $this->call([
            ContactSeeder::class,
            MediaSeeder::class,
            RedacteurDemoSeeder::class,
            HistoireSeeder::class,
            HistoireAnxieteSeeder::class,
        ]);
    }
}
