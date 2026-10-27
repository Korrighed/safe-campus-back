<?php

namespace Database\Factories;

use App\Models\SousTheme;
use App\Models\Theme;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SousTheme>
 */
class SousThemeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $libelle = fake()->unique()->words(2, true);

        return [
            'ref' => Str::slug($libelle, '_'),
            'libelle' => Str::ucfirst($libelle),
            'resume' => fake()->paragraph(),
            'article' => fake()->paragraphs(3, true),
            'theme_id' => Theme::factory(),
            'permet_signalement' => false,
            'actif' => true,
        ];
    }

    /**
     * Sous-theme retire : reste en base, disparait du front.
     */
    public function inactif(): static
    {
        return $this->state(fn (array $attributes) => [
            'actif' => false,
        ]);
    }

    /**
     * Sous-theme exposant le formulaire de signalement.
     */
    public function avecSignalement(): static
    {
        return $this->state(fn (array $attributes) => [
            'permet_signalement' => true,
        ]);
    }

    /**
     * Sous-theme cree mais dont la fiche n'est pas encore redigee.
     */
    public function sansArticle(): static
    {
        return $this->state(fn (array $attributes) => [
            'article' => null,
        ]);
    }
}
