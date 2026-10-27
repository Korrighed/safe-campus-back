<?php

use App\Models\SousTheme;
use App\Models\Theme;
use Database\Seeders\SousThemeSeeder;
use Database\Seeders\ThemeSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Taxonomie de reference (themes/sous-themes) : donnee structurelle requise
 * par l'application, pas une donnee de demo. Seedee au migrate pour etre
 * toujours presente, sans dependre du flag --seed. Voir
 * database/seeders/ThemeSeeder et SousThemeSeeder (idempotents via `ref`).
 */
return new class extends Migration
{
    public function up(): void
    {
        (new ThemeSeeder)->run();
        (new SousThemeSeeder)->run();
    }

    public function down(): void
    {
        SousTheme::query()->delete();
        Theme::query()->delete();
    }
};
