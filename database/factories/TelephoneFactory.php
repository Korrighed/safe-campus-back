<?php

namespace Database\Factories;

use App\Enums\TelephoneType;
use App\Models\Contact;
use App\Models\Telephone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Telephone>
 */
class TelephoneFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'numero' => fake()->numerify('## ## ##'),
            'numero_vert' => false,
            'type' => fake()->randomElement(TelephoneType::cases()),
            'libelle' => null,
            'contact_id' => Contact::factory(),
            'actif' => true,
        ];
    }

    /**
     * Ligne d'urgence joignable en continu.
     */
    public function urgence(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => TelephoneType::Urgence,
            'numero' => fake()->numerify('##'),
        ]);
    }

    /**
     * Numero gratuit depuis un poste fixe (0800...).
     */
    public function numeroVert(): static
    {
        return $this->state(fn (array $attributes) => [
            'numero_vert' => true,
        ]);
    }

    /**
     * Ligne identifiee au sein d'une meme structure : psychologue, intervenant social.
     */
    public function libelle(string $libelle): static
    {
        return $this->state(fn (array $attributes) => [
            'libelle' => $libelle,
        ]);
    }

    /**
     * Ligne retiree : reste en base, disparait du front.
     */
    public function inactif(): static
    {
        return $this->state(fn (array $attributes) => [
            'actif' => false,
        ]);
    }
}
