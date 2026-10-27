<?php

namespace App\Filament\Widgets;

use App\Enums\UserRole;
use Filament\Widgets\Widget;

class GuideWebmasterWidget extends Widget
{
    protected static string $view = 'filament.widgets.guide-webmaster';

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = -3;

    public static function canView(): bool
    {
        return auth()->user()?->role === UserRole::Webmaster;
    }
}
