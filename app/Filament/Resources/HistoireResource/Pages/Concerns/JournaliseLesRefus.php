<?php

namespace App\Filament\Resources\HistoireResource\Pages\Concerns;

use App\Services\AuditService;

/**
 * Trace un refus d'acces sur l'histoire ouverte. A utiliser avec LitHistoire.
 */
trait JournaliseLesRefus
{
    protected function journaliserRefus(string $capacite): void
    {
        app(AuditService::class)->ecrire('Histoire.acces_refuse', $this->histoire(), [
            'motif' => 'droit_refuse',
            'capacite' => $capacite,
        ]);
    }
}
