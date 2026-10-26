<?php

namespace App\Filament\Resources\HistoireResource\Pages;

use App\Enums\ChoixIssue;
use App\Filament\Resources\HistoireResource;
use App\Filament\Resources\HistoireResource\Pages\Concerns\EnTeteAvecOnglets;
use App\Filament\Resources\HistoireResource\Pages\Concerns\OuvreVerification;
use App\Models\Choix;
use App\Models\Contact;
use App\Models\Histoire;
use App\Models\Scene;
use App\Services\ContactResolutionService;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Database\Eloquent\Collection;

/**
 * Rejoue le parcours d'une histoire depuis l'admin, clic apres clic. Outil de
 * validation de la logique du graphe pour le redacteur — pas le rendu final
 * du front (qui n'existe pas encore, voir docs/schema_bd.md § Histoires).
 */
class PreviewHistoire extends Page
{
    use EnTeteAvecOnglets;
    use InteractsWithRecord;
    use OuvreVerification;

    protected static string $resource = HistoireResource::class;

    protected static string $view = 'filament.resources.histoire-resource.pages.preview-histoire';

    protected static ?string $navigationLabel = '3. Prévisualiser';

    protected static ?string $navigationIcon = 'heroicon-o-play';

    public ?int $sceneActuelleId = null;

    public ?int $choixFinalId = null;

    public function mount(int|string $record): void
    {
        // $record arrive en int brut : Livewire hydrate la propriete $record
        // (typee Model|int|string|null par le trait) avant meme d'appeler
        // mount(). resolveRecord() la remplace ici par le vrai modele.
        $this->record = $this->resolveRecord($record);

        abort_unless(auth()->user()?->can('view', $this->getRecord()), 403);

        $this->sceneActuelleId = $this->histoire()->sceneInitiale()?->id;
    }

    public function getTitle(): string
    {
        return 'Prévisualiser : '.$this->histoire()->titre;
    }

    /**
     * InteractsWithRecord type le record en Model : cette page ne sert que
     * HistoireResource.
     */
    private function histoire(): Histoire
    {
        /** @var Histoire $histoire */
        $histoire = $this->getRecord();

        return $histoire;
    }

    public function getSceneActuelle(): ?Scene
    {
        if ($this->sceneActuelleId === null) {
            return null;
        }

        return Scene::with(['choix', 'media'])->find($this->sceneActuelleId);
    }

    public function getChoixFinal(): ?Choix
    {
        if ($this->choixFinalId === null) {
            return null;
        }

        return Choix::find($this->choixFinalId);
    }

    public function choisir(int $choixId): void
    {
        // Seuls les choix de la scene affichee sont jouables : l'id vient du
        // navigateur et pourrait viser un choix d'une autre histoire.
        $choix = Choix::query()->where('scene_id', $this->sceneActuelleId)->findOrFail($choixId);

        if ($choix->next_scene_id === null) {
            $this->choixFinalId = $choix->id;
            $this->sceneActuelleId = null;

            return;
        }

        $this->choixFinalId = null;
        $this->sceneActuelleId = $choix->next_scene_id;
    }

    public function recommencer(): void
    {
        $this->sceneActuelleId = $this->histoire()->sceneInitiale()?->id;
        $this->choixFinalId = null;
    }

    /**
     * @return Collection<int, Contact>
     */
    public function getContacts(): Collection
    {
        $choixFinal = $this->getChoixFinal();

        if (! $choixFinal || $choixFinal->issue !== ChoixIssue::Defavorable) {
            return Contact::query()->whereRaw('1 = 0')->get();
        }

        return app(ContactResolutionService::class)->resoudre($this->histoire(), $choixFinal);
    }
}
