<?php

namespace App\Filament\Resources\HistoireResource\Pages\Concerns;

use App\Filament\Resources\HistoireResource;
use Filament\Actions\Action;
use Livewire\Attributes\On;

/**
 * Le menu est hors du composant Livewire : son lien émet « ouvrir-verification », reçu ici.
 */
trait OuvreVerification
{
    public function verifierAction(): Action
    {
        return HistoireResource::actionVerifier();
    }

    #[On('ouvrir-verification')]
    public function ouvrirVerification(): void
    {
        $this->mountAction('verifier');
    }
}
