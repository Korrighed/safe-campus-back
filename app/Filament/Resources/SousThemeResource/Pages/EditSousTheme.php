<?php

namespace App\Filament\Resources\SousThemeResource\Pages;

use App\Filament\Resources\SousThemeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSousTheme extends EditRecord
{
    protected static string $resource = SousThemeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
