<?php

namespace Database\Factories;

use App\Enums\ChoixIssue;
use App\Models\Choix;
use App\Models\Scene;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Choix>
 */
class ChoixFactory extends Factory
{
    /**
     * Par defaut un choix qui poursuit l'histoire : `issue` et `next_scene_id`
     * restent a renseigner explicitement (voir states `favorable`/`defavorable`).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'scene_id' => Scene::factory(),
            'next_scene_id' => Scene::factory(),
            'text_choix' => fake()->sentence(),
            'issue' => null,
        ];
    }

    /**
     * Bonne conduite : le parcours s'arrete, pas de scene suivante.
     */
    public function favorable(): static
    {
        return $this->state(fn (array $attributes) => [
            'next_scene_id' => null,
            'issue' => ChoixIssue::Favorable,
        ]);
    }

    /**
     * Mauvais choix de la scene finale : le parcours s'arrete, contacts affiches.
     */
    public function defavorable(): static
    {
        return $this->state(fn (array $attributes) => [
            'next_scene_id' => null,
            'issue' => ChoixIssue::Defavorable,
        ]);
    }
}
