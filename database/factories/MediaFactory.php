<?php

namespace Database\Factories;

use App\Enums\MediaType;
use App\Models\Media;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Media>
 */
class MediaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $libelle = fake()->unique()->words(3, true);

        return [
            'libelle' => Str::ucfirst($libelle),
            'chemin' => 'medias/'.Str::slug($libelle).'.png',
            'type' => MediaType::Image,
            'actif' => true,
        ];
    }

    public function type(MediaType $type): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type,
        ]);
    }

    /**
     * Media retire : reste en base, disparait du front.
     */
    public function inactif(): static
    {
        return $this->state(fn (array $attributes) => [
            'actif' => false,
        ]);
    }
}
