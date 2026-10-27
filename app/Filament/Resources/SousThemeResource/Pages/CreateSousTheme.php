<?php

namespace App\Filament\Resources\SousThemeResource\Pages;

use App\Filament\Resources\SousThemeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSousTheme extends CreateRecord
{
    protected static string $resource = SousThemeResource::class;

    public function mount(): void
    {
        parent::mount();

        if ($themeId = request()->integer('theme_id')) {
            $this->form->fill([...$this->data, 'theme_id' => $themeId]);
        }
    }
}
