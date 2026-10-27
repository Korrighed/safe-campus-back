<?php

namespace App\Filament\Resources\HistoireResource\Pages\Concerns;

use Illuminate\Contracts\View\View;

/**
 * Onglets sur la ligne du fil d'Ariane, position que Filament ne propose pas.
 */
trait EnTeteAvecOnglets
{
    public function getHeader(): ?View
    {
        return view('filament.resources.histoire-resource.en-tete', [
            'actions' => $this->getCachedHeaderActions(),
            'breadcrumbs' => filament()->hasBreadcrumbs() ? $this->getBreadcrumbs() : [],
            'heading' => $this->getHeading(),
            'onglets' => parent::getCachedSubNavigation(),
        ]);
    }

    /**
     * Vide : les onglets sont déjà dans l'en-tête.
     */
    public function getCachedSubNavigation(): array
    {
        return [];
    }
}
