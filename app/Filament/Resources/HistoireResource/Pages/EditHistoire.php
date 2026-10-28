<?php

namespace App\Filament\Resources\HistoireResource\Pages;

use App\Enums\EtatHistoire;
use App\Filament\Resources\HistoireResource;
use App\Filament\Resources\HistoireResource\Pages\Concerns\EnTeteAvecOnglets;
use App\Filament\Resources\HistoireResource\Pages\Concerns\JournaliseLesRefus;
use App\Filament\Resources\HistoireResource\Pages\Concerns\LitHistoire;
use App\Filament\Resources\HistoireResource\Pages\Concerns\OuvreVerification;
use App\Services\AuditService;
use App\Services\VerificationHistoireService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class EditHistoire extends EditRecord
{
    use EnTeteAvecOnglets;
    use JournaliseLesRefus;
    use LitHistoire;
    use OuvreVerification;

    protected static string $resource = HistoireResource::class;

    protected static ?string $navigationLabel = '1. Infos de l\'histoire';

    protected static ?string $navigationIcon = 'heroicon-o-information-circle';

    /**
     * Ids des sous-themes avant l'enregistrement, lus dans beforeSave().
     *
     * @var list<int>
     */
    private array $sousThemesAvant = [];

    public function getTitle(): string|Htmlable
    {
        return $this->histoire()->titre;
    }

    public function getBreadcrumb(): string
    {
        return 'Infos';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ActionGroup::make([
                Actions\DeleteAction::make()
                    ->authorize('delete'),
            ]),
        ];
    }

    /**
     * Page Infos refusée (histoire verrouillée, droit absent) : tracée avant la 403.
     * Appelée par mount(), hydrate() et save() de EditRecord.
     */
    protected function authorizeAccess(): void
    {
        try {
            parent::authorizeAccess();
        } catch (HttpException $exception) {
            $this->journaliserRefus('update');

            throw $exception;
        }
    }

    /**
     * Un redacteur qui passe son histoire en relecture la verrouille : la page
     * n'est plus accessible, on le renvoie a la liste au lieu de lui laisser
     * un formulaire dont la prochaine requete repondrait 403.
     */
    protected function getRedirectUrl(): ?string
    {
        return auth()->user()?->can('update', $this->histoire())
            ? null
            : HistoireResource::getUrl('index');
    }

    /**
     * Appelee par save() avant saveRelationships() : les sous-themes lus ici
     * sont ceux d'avant l'ecriture.
     */
    protected function beforeSave(): void
    {
        $this->sousThemesAvant = $this->histoire()->sousThemes()->pluck('sous_themes.id')->map(fn ($id) => (int) $id)->sort()->values()->all();
    }

    /**
     * Avertit sans bloquer : une histoire incomplete peut quand meme changer
     * d'etat, le redacteur reste seul juge.
     */
    protected function afterSave(): void
    {
        $apres = $this->histoire()->sousThemes()->pluck('sous_themes.id')->map(fn ($id) => (int) $id)->sort()->values()->all();
        $ajoutes = array_values(array_diff($apres, $this->sousThemesAvant));
        $retires = array_values(array_diff($this->sousThemesAvant, $apres));

        // save() n'ouvre pas de transaction cote panel : DB::afterCommit s'execute alors immediatement, et reste correct si databaseTransactions() est active un jour.
        if ($ajoutes !== [] || $retires !== []) {
            $histoire = $this->histoire();

            DB::afterCommit(fn () => app(AuditService::class)->ecrire('Histoire.sous_themes_modifies', $histoire, ['ajoutes' => $ajoutes, 'retires' => $retires]));
        }

        if ($this->histoire()->etat === EtatHistoire::Brouillon) {
            return;
        }

        $problemes = app(VerificationHistoireService::class)->problemes($this->histoire());

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
