<?php

namespace App\Filament\Resources\HistoireResource\Pages;

use App\Enums\ChoixIssue;
use App\Filament\Resources\HistoireResource;
use App\Filament\Resources\HistoireResource\Pages\Concerns\EnTeteAvecOnglets;
use App\Filament\Resources\HistoireResource\Pages\Concerns\LitHistoire;
use App\Filament\Resources\HistoireResource\Pages\Concerns\OuvreVerification;
use App\Models\Choix;
use App\Models\Histoire;
use App\Models\Media;
use App\Models\Scene;
use App\Models\SousTheme;
use App\Services\VerificationHistoireService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Navigation\NavigationItem;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Page Scènes : une section par scène, modifiée sur place. Seules les confirmations de retrait et de suppression ouvrent une fenêtre.
 *
 * @property Form $form
 */
class ScenesHistoire extends Page
{
    use EnTeteAvecOnglets;
    use InteractsWithRecord;
    use LitHistoire;
    use OuvreVerification;

    protected static string $resource = HistoireResource::class;

    protected static string $view = 'filament.resources.histoire-resource.pages.scenes-histoire';

    protected static ?string $navigationLabel = '2. Scènes';

    protected static ?string $navigationIcon = 'heroicon-o-film';

    /**
     * id de la scène ouverte, 0 pour une nouvelle, null sinon.
     */
    public ?int $sceneEnEdition = null;

    public bool $reutilisationOuverte = false;

    public ?int $sceneAReutiliser = null;

    /**
     * Mémo : options évaluées pour chaque ligne du repeater.
     *
     * @var array<int, string>|null
     */
    protected ?array $optionsContinuerVers = null;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $donnees = [];

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        abort_unless(auth()->user()?->can('view', $this->histoire()), 403);

        $this->form->fill();
    }

    /**
     * @param  array<string, mixed>  $urlParameters
     * @return array<NavigationItem>
     */
    public static function getNavigationItems(array $urlParameters = []): array
    {
        $items = parent::getNavigationItems($urlParameters);
        $histoire = $urlParameters['record'] ?? null;

        if (! $histoire instanceof Histoire) {
            return $items;
        }

        foreach ($items as $item) {
            $item->badge((string) $histoire->scenes()->count(), 'gray');
        }

        return $items;
    }

    public function getTitle(): string|Htmlable
    {
        return $this->histoire()->titre;
    }

    public function getBreadcrumb(): string
    {
        return 'Scènes';
    }

    public function peutModifier(): bool
    {
        return auth()->user()?->can('update', $this->histoire()) ?? false;
    }

    /**
     * @return list<array{scene: Scene, numero: int, estDepart: bool, estPremiere: bool, estDerniere: bool, modifiable: bool, badges: list<array{libelle: string, couleur: string}>, lignes: list<array{texte: string, suite: string, couleur: string}>}>
     */
    public function getSections(): array
    {
        $scenes = $this->scenesOrdonnees();
        $numeros = $scenes->pluck('id')->flip()->map(fn (int $index) => $index + 1);
        $atteignables = app(VerificationHistoireService::class)->scenesAtteignables($this->histoire());

        return $scenes->values()->map(fn (Scene $scene, int $index) => [
            'scene' => $scene,
            'numero' => $index + 1,
            'estDepart' => (bool) $scene->pivot->getAttribute('est_initiale'),
            'estPremiere' => $index === 0,
            'estDerniere' => $index === $scenes->count() - 1,
            'modifiable' => auth()->user()?->can('update', $scene) ?? false,
            'badges' =>$this->badges($scene, in_array($scene->id, $atteignables, true)),
            'lignes' => $scene->choix
                ->map(fn (Choix $choix) => ['texte' => $choix->text_choix, ...$this->suite($choix, $numeros->all())])
                ->all(),
        ])->all();
    }

    /**
     * Choix « Continuer » sans scène suivante.
     *
     * @return list<array{sceneId: int, numero: int, scene: string, texte: string}>
     */
    public function getChoixEnAttente(): array
    {
        $enAttente = [];

        foreach ($this->scenesOrdonnees()->values() as $index => $scene) {
            foreach ($scene->choix as $choix) {
                if ($choix->next_scene_id === null && $choix->issue === null) {
                    $enAttente[] = ['sceneId' => $scene->id, 'numero' => $index + 1, 'scene' => $scene->titre, 'texte' => $choix->text_choix];
                }
            }
        }

        return $enAttente;
    }

    /**
     * @return array<int, string>
     */
    public function getScenesReutilisables(): array
    {
        return Scene::query()
            ->whereDoesntHave('histoires', fn ($query) => $query->whereKey($this->histoire()->getKey()))
            ->whereHas('histoires')
            ->whereDoesntHave('histoires', fn ($query) => $query->whereNotIn('histoires.id', Histoire::query()->visiblePar(auth()->user())->select('histoires.id')))
            ->with('histoires:id,titre')
            ->orderBy('titre')
            ->get()
            ->mapWithKeys(fn (Scene $scene) => [
                $scene->id => "{$scene->titre} — histoire « {$scene->histoires->first()->titre} »",
            ])
            ->all();
    }

    /**
     * Choix sans relation, synchronisés dans enregistrer(). Un choix ne crée pas de scène : « Continuer vers » peut rester vide.
     */
    public function form(Form $form): Form
    {
        return $form
            ->statePath('donnees')
            ->schema([
                Forms\Components\TextInput::make('titre')
                    ->label('Nom de la scène')
                    ->required()
                    ->maxLength(255)
                    ->helperText('Repère pour toi, jamais montré au joueur.'),
                Forms\Components\Textarea::make('dialogue_text')
                    ->label('Mise en situation')
                    ->required()
                    ->rows(4)
                    ->helperText('Le texte que lit le joueur avant de choisir.'),
                Forms\Components\Section::make('Média et thème')
                    ->description(fn (Get $get): string => $this->resumeMediaEtTheme($get('media_id'), $get('sous_theme_id')))
                    ->collapsible()
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('media_id')
                            ->label('Image de la scène')
                            ->options(fn (): array => Media::query()->orderBy('libelle')->pluck('libelle', 'id')->all())
                            ->in(fn (Forms\Components\Select $component): array => array_keys($component->getOptions()))
                            ->placeholder('Aucune image')
                            ->searchable()
                            ->live()
                            ->helperText('À ajouter d\'abord depuis la page Médias.'),
                        Forms\Components\Select::make('sous_theme_id')
                            ->label('Change de thème à partir de cette scène')
                            ->options(fn (): array => SousTheme::query()->orderBy('libelle')->pluck('libelle', 'id')->all())
                            ->in(fn (Forms\Components\Select $component): array => array_keys($component->getOptions()))
                            ->placeholder('Non, garder le thème de l\'histoire')
                            ->searchable()
                            ->live()
                            ->helperText('Un seul thème possible. Les contacts d\'aide suivent ce thème.'),
                    ]),
                Forms\Components\Repeater::make('choix')
                    ->label('Choix du joueur')
                    ->helperText('Pour chaque choix : ce que le joueur peut répondre, puis ce qui se passe ensuite. Un choix ne peut mener qu\'à une scène qui existe déjà.')
                    ->defaultItems(0)
                    ->reorderable(false)
                    ->addActionLabel('Ajouter un choix')
                    ->schema([
                        Forms\Components\Hidden::make('id'),
                        Forms\Components\Grid::make(['default' => 1, 'lg' => 12])
                            ->schema([
                                Forms\Components\TextInput::make('text_choix')
                                    ->label('Ce que le joueur peut répondre')
                                    ->hiddenLabel()
                                    ->placeholder('Ce que le joueur peut répondre')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(['lg' => 4]),
                                Forms\Components\ToggleButtons::make('suite')
                                    ->label('Que se passe-t-il ensuite ?')
                                    ->hiddenLabel()
                                    ->options([
                                        ChoixIssue::Favorable->value => 'Bonne fin',
                                        'continuer' => 'Continuer',
                                        ChoixIssue::Defavorable->value => 'Fin à risque',
                                    ])
                                    ->colors([
                                        ChoixIssue::Favorable->value => 'success',
                                        'continuer' => 'primary',
                                        ChoixIssue::Defavorable->value => 'danger',
                                    ])
                                    ->grouped()
                                    ->in(['continuer', ChoixIssue::Favorable->value, ChoixIssue::Defavorable->value])
                                    ->required()
                                    ->default('continuer')
                                    ->live()
                                    ->helperText(fn (Get $get): ?string => $get('suite') === ChoixIssue::Defavorable->value
                                        ? 'Les contacts d\'aide s\'affichent au joueur.'
                                        : null)
                                    ->columnSpan(['lg' => 5]),
                                Forms\Components\Select::make('next_scene_id')
                                    ->label('Continuer vers')
                                    ->hiddenLabel()
                                    ->placeholder('Continuer vers… (choisir une scène)')
                                    ->options(fn (): array => $this->optionsContinuerVers())
                                    ->in(fn (): array => array_keys($this->optionsContinuerVers()))
                                    ->visible(fn (Get $get): bool => $get('suite') === 'continuer')
                                    ->helperText('Scène pas encore écrite ? Laisse vide, enregistre, puis crée-la avec « Nouvelle scène ».')
                                    ->columnSpan(['lg' => 3]),
                            ]),
                    ]),
            ]);
    }

    public function modifier(int $sceneId): void
    {
        if ($this->sceneEnEdition !== null) {
            return;
        }

        $scene = $this->sceneDeLHistoire($sceneId);
        abort_unless(auth()->user()?->can('update', $scene), 403);

        $this->form->fill([
            'titre' => $scene->titre,
            'dialogue_text' => $scene->dialogue_text,
            'media_id' => $scene->media_id,
            'sous_theme_id' => $scene->sous_theme_id,
            'choix' => $scene->choix->mapWithKeys(fn (Choix $choix) => ['choix-'.$choix->id => [
                'id' => $choix->id,
                'text_choix' => $choix->text_choix,
                'suite' => $choix->issue->value ?? ($choix->next_scene_id !== null ? 'continuer' : null),
                'next_scene_id' => $choix->next_scene_id,
            ]])->all(),
        ]);

        $this->sceneEnEdition = $sceneId;
        $this->reutilisationOuverte = false;
    }

    public function nouvelleScene(): void
    {
        if ($this->sceneEnEdition !== null) {
            return;
        }

        $this->autoriserModification();
        $this->form->fill(['choix' => []]);
        $this->sceneEnEdition = 0;
        $this->reutilisationOuverte = false;
    }

    public function annuler(): void
    {
        $this->sceneEnEdition = null;
        $this->form->fill();
    }

    public function enregistrer(): void
    {
        if ($this->sceneEnEdition === null) {
            return;
        }

        $this->autoriserModification();
        $donnees = $this->form->getState();

        $estNouvelle = $this->sceneEnEdition === 0;

        $scene = DB::transaction(function () use ($donnees, $estNouvelle): Scene {
            $scene = $estNouvelle ? new Scene : $this->sceneDeLHistoire($this->sceneEnEdition);
            abort_unless($estNouvelle || auth()->user()?->can('update', $scene), 403);

            $scene->fill(Arr::only($donnees, ['titre', 'dialogue_text', 'media_id', 'sous_theme_id']))->save();

            if ($estNouvelle) {
                $this->rattacher($scene);
            }

            $conserves = [];

            foreach ($donnees['choix'] ?? [] as $ligne) {
                $continue = $ligne['suite'] === 'continuer';
                $cible = $ligne['next_scene_id'] ?? null;

                $attributs = [
                    'text_choix' => $ligne['text_choix'],
                    'next_scene_id' => $continue && filled($cible) ? (int) $cible : null,
                    'issue' => $continue ? null : $ligne['suite'],
                ];

                $choix = filled($ligne['id'] ?? null) ? $scene->choix()->whereKey($ligne['id'])->first() : null;

                if ($choix) {
                    $choix->update($attributs);
                } else {
                    $choix = $scene->choix()->create($attributs);
                }

                $conserves[] = $choix->getKey();
            }

            $scene->choix()->whereNotIn('id', $conserves)->delete();

            return $scene;
        });

        $this->sceneEnEdition = null;
        $this->form->fill();

        $nbEnAttente = count($this->getChoixEnAttente());

        Notification::make()
            ->success()
            ->title("« {$scene->titre} » est enregistrée.")
            ->body($estNouvelle && $nbEnAttente > 0
                ? "{$nbEnAttente} choix attendent encore leur scène suivante : ouvre leur scène avec « Modifier » pour les relier à « {$scene->titre} » si besoin."
                : null)
            ->send();
    }

    /**
     * L'index unique partiel (une seule scene initiale par histoire) impose
     * de retirer l'ancien depart avant de poser le nouveau.
     */
    public function definirDepart(int $sceneId): void
    {
        $scene = $this->sceneDeLHistoire($sceneId);
        $relation = $this->histoire()->scenes();

        DB::transaction(function () use ($relation, $scene): void {
            $relation->newPivotQuery()->update(['est_initiale' => false]);
            $relation->updateExistingPivot($scene->getKey(), ['est_initiale' => true]);
        });

        Notification::make()->success()->title("« {$scene->titre} » est maintenant la scène de départ.")->send();
    }

    public function monter(int $sceneId): void
    {
        $this->deplacer($sceneId, -1);
    }

    public function descendre(int $sceneId): void
    {
        $this->deplacer($sceneId, 1);
    }

    public function reutiliser(): void
    {
        $this->autoriserModification();

        $scene = Scene::query()
            ->whereKey($this->sceneAReutiliser)
            ->whereDoesntHave('histoires', fn ($query) => $query->whereKey($this->histoire()->getKey()))
            ->whereHas('histoires')
            ->whereDoesntHave('histoires', fn ($query) => $query->whereNotIn('histoires.id', Histoire::query()->visiblePar(auth()->user())->select('histoires.id')))
            ->first();

        if ($scene === null) {
            Notification::make()->warning()->title('Choisis une scène à réutiliser.')->send();

            return;
        }

        $this->rattacher($scene);
        $this->reutilisationOuverte = false;
        $this->sceneAReutiliser = null;

        Notification::make()->success()->title("« {$scene->titre} » est ajoutée à l'histoire.")->send();
    }

    public function retirerSceneAction(): Action
    {
        return Action::make('retirerScene')
            ->requiresConfirmation()
            ->modalHeading(fn (array $arguments): string => 'Retirer « '.$this->sceneDeLHistoire((int) ($arguments['scene'] ?? 0))->titre.' » de l\'histoire ?')
            ->modalDescription('La scène n\'est pas supprimée : elle reste disponible pour être réutilisée.')
            ->modalSubmitActionLabel('Retirer')
            ->action(function (array $arguments): void {
                $scene = $this->sceneDeLHistoire((int) ($arguments['scene'] ?? 0));
                $this->histoire()->scenes()->detach($scene->getKey());

                Notification::make()->success()->title("« {$scene->titre} » est retirée de l'histoire.")->send();
            });
    }

    public function supprimerSceneAction(): Action
    {
        return Action::make('supprimerScene')
            ->requiresConfirmation()
            ->color('danger')
            ->modalHeading(fn (array $arguments): string => 'Supprimer « '.$this->sceneDeLHistoire((int) ($arguments['scene'] ?? 0))->titre.' » ?')
            ->modalDescription('La scène et ses choix seront supprimés définitivement.')
            ->modalSubmitActionLabel('Supprimer')
            ->action(function (array $arguments): void {
                $scene = $this->sceneDeLHistoire((int) ($arguments['scene'] ?? 0));

                if (! auth()->user()?->can('delete', $scene)) {
                    Notification::make()
                        ->warning()
                        ->title('Cette scène ne peut pas être supprimée.')
                        ->body('Elle est utilisée par une histoire que vous ne pouvez pas modifier.')
                        ->send();

                    return;
                }

                if ($scene->histoires()->whereKeyNot($this->histoire()->getKey())->exists()) {
                    Notification::make()
                        ->warning()
                        ->title('Cette scène est utilisée par une autre histoire.')
                        ->body('Retirez-la de cette histoire plutôt que de la supprimer.')
                        ->send();

                    return;
                }

                $scene->delete();

                Notification::make()->success()->title("« {$scene->titre} » est supprimée.")->send();
            });
    }

    /**
     * @return Collection<int, Scene>
     */
    protected function scenesOrdonnees(): Collection
    {
        return $this->histoire()->scenes()
            ->with('choix.nextScene')
            ->orderBy('histoire_scene.ordre')
            ->orderBy('scenes.id')
            ->get();
    }

    protected function sceneDeLHistoire(int $sceneId): Scene
    {
        $this->autoriserModification();

        return $this->histoire()->scenes()->whereKey($sceneId)->firstOrFail();
    }

    protected function autoriserModification(): void
    {
        abort_unless(auth()->user()?->can('update', $this->histoire()), 403);
    }

    /**
     * Rattache une scene en fin de liste ; elle devient le depart si
     * l'histoire n'en a pas.
     */
    protected function rattacher(Scene $scene): void
    {
        $histoire = $this->histoire();

        $histoire->scenes()->attach($scene->getKey(), [
            'ordre' => (int) $histoire->scenes()->max('histoire_scene.ordre') + 1,
            'est_initiale' => $histoire->sceneInitiale() === null,
        ]);
    }

    /**
     * Permute la scene avec sa voisine, apres renumerotation 1..n : les
     * histoires anciennes peuvent porter des ordres en double.
     */
    protected function deplacer(int $sceneId, int $sens): void
    {
        $this->sceneDeLHistoire($sceneId);

        $ids = $this->scenesOrdonnees()->modelKeys();
        $index = array_search($sceneId, $ids, true);
        $voisin = $index + $sens;

        if ($index === false || ! isset($ids[$voisin])) {
            return;
        }

        [$ids[$index], $ids[$voisin]] = [$ids[$voisin], $ids[$index]];
        $relation = $this->histoire()->scenes();

        DB::transaction(function () use ($ids, $relation): void {
            foreach ($ids as $position => $id) {
                $relation->updateExistingPivot($id, ['ordre' => $position + 1]);
            }
        });
    }

    /**
     * @return array<int, string>
     */
    protected function optionsContinuerVers(): array
    {
        if ($this->optionsContinuerVers !== null) {
            return $this->optionsContinuerVers;
        }

        $options = [];

        foreach ($this->scenesOrdonnees()->values() as $index => $scene) {
            if ($scene->id !== $this->sceneEnEdition) {
                $options[$scene->id] = 'Scène '.($index + 1).' — '.$scene->titre;
            }
        }

        return $this->optionsContinuerVers = $options;
    }

    protected function resumeMediaEtTheme(mixed $mediaId, mixed $sousThemeId): string
    {
        $media = filled($mediaId) ? Media::query()->whereKey($mediaId)->value('libelle') : null;
        $sousTheme = filled($sousThemeId) ? SousTheme::query()->whereKey($sousThemeId)->value('libelle') : null;

        return ($media ?? 'Aucune image').' · '.($sousTheme !== null ? "thème « {$sousTheme} »" : 'thème de l\'histoire');
    }

    /**
     * @return list<array{libelle: string, couleur: string}>
     */
    protected function badges(Scene $scene, bool $atteignable): array
    {
        $badges = [];
        $estDepart = (bool) $scene->pivot->getAttribute('est_initiale');

        if ($estDepart) {
            $badges[] = ['libelle' => 'Départ', 'couleur' => 'success'];
        }

        if ($scene->choix->contains(fn (Choix $choix) => $choix->issue !== null)) {
            $badges[] = ['libelle' => 'Fin', 'couleur' => 'info'];
        }

        if (blank($scene->dialogue_text)) {
            $badges[] = ['libelle' => 'Mise en situation à écrire', 'couleur' => 'warning'];
        }

        if ($scene->choix->isEmpty()) {
            $badges[] = ['libelle' => 'Aucun choix', 'couleur' => 'warning'];
        } elseif ($scene->choix->contains(fn (Choix $choix) => $choix->next_scene_id === null && $choix->issue === null)) {
            $badges[] = ['libelle' => 'Choix à compléter', 'couleur' => 'warning'];
        }

        if (! $estDepart && ! $atteignable) {
            $badges[] = ['libelle' => 'Jamais atteinte', 'couleur' => 'warning'];
        }

        return $badges;
    }

    /**
     * @param  array<int, int>  $numeros  id de scene => numero affiche
     * @return array{suite: string, couleur: string}
     */
    protected function suite(Choix $choix, array $numeros): array
    {
        return match (true) {
            $choix->next_scene_id !== null && isset($numeros[$choix->next_scene_id]) => [
                'suite' => 'Scène '.$numeros[$choix->next_scene_id].' — '.Str::limit($choix->nextScene->titre ?? '', 60),
                'couleur' => 'primary',
            ],
            $choix->next_scene_id !== null => ['suite' => 'Scène hors de cette histoire', 'couleur' => 'warning'],
            $choix->issue === ChoixIssue::Favorable => ['suite' => ChoixIssue::Favorable->libelle(), 'couleur' => 'success'],
            $choix->issue === ChoixIssue::Defavorable => ['suite' => ChoixIssue::Defavorable->libelle(), 'couleur' => 'danger'],
            default => ['suite' => 'Suite à choisir', 'couleur' => 'warning'],
        };
    }
}
