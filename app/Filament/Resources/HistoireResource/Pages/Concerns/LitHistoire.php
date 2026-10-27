<?php

namespace App\Filament\Resources\HistoireResource\Pages\Concerns;

use App\Models\Histoire;

/**
 * InteractsWithRecord et EditRecord typent le record en Model : ces pages ne
 * servent que HistoireResource.
 */
trait LitHistoire
{
    protected function histoire(): Histoire
    {
        /** @var Histoire $histoire */
        $histoire = $this->getRecord();

        return $histoire;
    }
}
