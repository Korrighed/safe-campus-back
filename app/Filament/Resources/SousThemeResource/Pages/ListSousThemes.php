<?php

namespace App\Filament\Resources\SousThemeResource\Pages;

use App\Filament\Resources\SousThemeResource;
use App\Models\Theme;
use Filament\Resources\Pages\Page;
use Illuminate\Database\Eloquent\Collection;

class ListSousThemes extends Page
{
    protected static string $resource = SousThemeResource::class;

    protected static string $view = 'filament.resources.sous-theme-resource.pages.list-sous-themes';

    public function getSubheading(): ?string
    {
        return 'Un sous-thème est une thématique précise rattachée à un thème, par exemple « Alcool » rattaché à « Addiction ».';
    }

    /**
     * @return Collection<int, Theme>
     */
    protected function getThemes(): Collection
    {
        return Theme::query()->ordonne()->get();
    }

    protected function getViewData(): array
    {
        return [
            'themes' => $this->getThemes(),
        ];
    }
}
