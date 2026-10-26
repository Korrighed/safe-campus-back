<?php

namespace App\Filament\Resources\HistoireResource\Pages;

use App\Enums\EtatHistoire;
use App\Filament\Resources\HistoireResource;
use App\Filament\Resources\HistoireResource\Pages\Concerns\EnTeteAvecOnglets;
use App\Filament\Resources\HistoireResource\Pages\Concerns\OuvreVerification;
use App\Services\VerificationHistoireService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditHistoire extends EditRecord
{
    use EnTeteAvecOnglets;
    use OuvreVerification;

    protected static string $resource = HistoireResource::class;

    protected static ?string $navigationLabel = '1. Infos de l\'histoire';

    protected static ?string $navigationIcon = 'heroicon-o-information-circle';

    public function getTitle(): string|Htmlable
    {
        return $this->getRecord()->titre;
    }

    public function getBreadcrumb(): string
    {
        return 'Infos';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ActionGroup::make([
                Actions\DeleteAction::make(),
            ]),
        ];
    }

    /**
     * Avertit sans bloquer : une histoire incomplete peut quand meme changer
     * d'etat, le redacteur reste seul juge.
     */
    protected function afterSave(): void
    {
        if ($this->getRecord()->etat === EtatHistoire::Brouillon) {
            return;
        }

        $problemes = app(VerificationHistoireService::class)->problemes($this->getRecord());

        if ($problemes === []) {
            return;
        }

        Notification::make()
            ->warning()
            ->title('Enregistrée, mais l\'histoire semble incomplète')
            ->body(implode("\n", array_map(fn (string $probleme) => "• {$probleme}", $problemes)))
            ->persistent()
            ->send();
    }
}
