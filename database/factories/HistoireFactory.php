<?php

namespace Database\Factories;

use App\Enums\EtatHistoire;
use App\Models\Histoire;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Histoire>
 */
class HistoireFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $titre = fake()->unique()->sentence(3);

        return [
            'ref' => Str::slug($titre, '_'),
            'titre' => $titre,
            'etat' => EtatHistoire::Brouillon,
            'user_id' => User::factory(),
        ];
    }

    /**
     * Histoire visible du front.
     */
    public function publiee(): static
    {
        return $this->state(fn (array $attributes) => [
            'etat' => EtatHistoire::Publie,
        ]);
    }
}
