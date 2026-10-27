<?php

namespace Database\Factories;

use App\Models\Scene;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Scene>
 */
class SceneFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'titre' => fake()->unique()->words(3, true),
            'dialogue_text' => fake()->paragraph(),
            'sous_theme_id' => null,
            'media_id' => null,
        ];
    }
}
