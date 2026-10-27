<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Field;

/**
 * Affiche un pin Leaflet/OpenStreetMap synchronise avec les champs
 * "latitude"/"longitude" du meme formulaire (Livewire entangle, temps reel).
 * Le pin est deplacable : le glisser corrige latitude/longitude directement.
 * Ne geocode pas lui-meme — c'est le role du bouton "Geocoder" sur le champ
 * adresse (cf. ContactResource::form()).
 */
class MapField extends Field
{
    protected string $view = 'filament.forms.components.map-field';

    protected function setUp(): void
    {
        parent::setUp();

        $this->dehydrated(false);
        $this->columnSpanFull();
    }
}
