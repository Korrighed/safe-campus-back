<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Geocodage via Nominatim (OpenStreetMap), gratuit et sans cle API.
 * Volume attendu tres faible (creation manuelle de contacts) : la limite
 * d'1 requete/seconde de Nominatim n'est pas une contrainte ici.
 */
class GeocodingService
{
    /**
     * @return array{latitude: float, longitude: float}|null
     */
    public function geocode(string $address): ?array
    {
        $response = Http::withHeaders([
            'User-Agent' => 'Safe-Campus-Admin/1.0 (+'.config('app.url').')',
        ])->get('https://nominatim.openstreetmap.org/search', [
            'q' => $address,
            'format' => 'json',
            'limit' => 1,
            // OSM/Nominatim rattache la Nouvelle-Caledonie a la France (ISO3166-2: FR-NC),
            // pas au code pays "nc" : filtrer sur "nc" excluait tous les resultats calédoniens.
            'countrycodes' => 'fr',
        ]);

        if ($response->failed()) {
            return null;
        }

        $result = $response->json('0');

        if ($result === null) {
            return null;
        }

        return [
            'latitude' => (float) $result['lat'],
            'longitude' => (float) $result['lon'],
        ];
    }
}
