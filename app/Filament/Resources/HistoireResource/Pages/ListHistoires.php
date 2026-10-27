<?php

namespace App\Filament\Resources\HistoireResource\Pages;

use App\Filament\Resources\HistoireResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHistoires extends ListRecords
{
    protected static string $resource = HistoireResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nouvelle histoire'),
        ];
    }

    public function getSubheading(): ?string
    {
        return 'Un parcours interactif à embranchements, du type « livre dont vous êtes le héros ».';
    }
}
