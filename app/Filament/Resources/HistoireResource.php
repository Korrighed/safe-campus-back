<?php

namespace App\Filament\Resources;

use App\Enums\EtatHistoire;
use App\Enums\UserRole;
use App\Filament\Resources\HistoireResource\Pages;
use App\Models\Histoire;
use App\Policies\HistoirePolicy;
use App\Services\AuditService;
use App\Services\VerificationHistoireService;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Navigation\NavigationItem;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class HistoireResource extends Resource
{
    protected static ?string $model = Histoire::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?int $navigationSort = 5;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public const TITRE_MAX = 255;

    public const DIALOGUE_MAX = 1000;

    public const TEXTE_CHOIX_MAX = 255;

    public static function mentionLimite(int $max): string
    {
        return "{$max} caractères maximum.";
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->visiblePar(auth()->user());
    }

    /**
     * Une histoire hors du périmètre de l'utilisateur reste un 404 (son
     * existence n'est pas révélée), mais la tentative est tracée.
     */
    public static function resolveRecordRouteBinding(int|string $key): ?Model
    {
        $histoire = parent::resolveRecordRouteBinding($key);

        if ($histoire === null && auth()->check() && $existante = Histoire::query()->whereKey($key)->first()) {
            app(AuditService::class)->ecrire('Histoire.acces_refuse', $existante, ['motif' => 'hors_perimetre']);
        }

        return $histoire;
    }

    /**
     * Formulaire d'édition seulement : la création passe par l'assistant.
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                static::champTitre(),
                                Forms\Components\Select::make('etat')
                                    ->label('État')
                                    ->options(fn (?Histoire $record): array => static::optionsEtat($record))
                                    ->required()
                                    ->in(fn (?Histoire $record): array => array_keys(static::optionsEtat($record)))
                                    ->native(false)
                                    ->live()
                                    ->helperText(fn (): string => auth()->user()?->role === UserRole::Relecteur
                                        ? 'Brouillon → Relecture → Validé → Publié. Seul « Publié » est visible sur le site. Vous pouvez passer d\'un état à n\'importe quel autre.'
                                        : 'Passez l\'histoire en « Relecture » quand elle est prête. Elle sera alors verrouillée : un relecteur la corrige, la publie ou vous la renvoie en brouillon.')
                                    ->hint(fn (Get $get, ?Histoire $record): ?string => self::histoireIncompleteHorsBrouillon($get('etat'), $record)
                                        ? 'Histoire incomplète — voir « Vérifier » dans le menu'
                                        : null)
                                    ->hintIcon('heroicon-o-exclamation-triangle')
                                    ->hintColor('warning'),
                            ]),
                        static::champSousThemes(),
                        Forms\Components\Placeholder::make('auteur')
                            ->label('Auteur')
                            ->content(fn (?Histoire $record): ?string => $record?->user?->getFilamentName()),
                    ]),
            ]);
    }

    public static function champTitre(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('titre')
            ->required()
            ->maxLength(self::TITRE_MAX)
            ->helperText(self::mentionLimite(self::TITRE_MAX));
    }

    public static function champSousThemes(): Forms\Components\Select
    {
        return Forms\Components\Select::make('sousThemes')
            ->label('Sous-thèmes')
            ->relationship('sousThemes', 'libelle')
            ->multiple()
            ->preload()
            ->searchable()
            ->helperText('Rubriques où apparaîtra le bouton « Suivre l\'histoire ».');
    }

    /**
     * Etats que l'utilisateur connecte peut poser sur cette histoire.
     *
     * @return array<string, string> valeur => libelle
     */
    public static function optionsEtat(?Histoire $histoire): array
    {
        $utilisateur = auth()->user();

        if ($histoire === null || $utilisateur === null) {
            return [];
        }

        return collect(app(HistoirePolicy::class)->etatsAccessibles($utilisateur, $histoire))
            ->mapWithKeys(fn (EtatHistoire $etat): array => [$etat->value => $etat->libelle()])
            ->all();
    }

    private static function histoireIncompleteHorsBrouillon(EtatHistoire|string|null $etat, ?Histoire $record): bool
    {
        $etat = $etat instanceof EtatHistoire ? $etat : EtatHistoire::tryFrom((string) $etat);

        if ($record === null || $etat === null || $etat === EtatHistoire::Brouillon) {
            return false;
        }

        return app(VerificationHistoireService::class)->problemes($record) !== [];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('user'))
            ->columns([
                Tables\Columns\TextColumn::make('titre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('ref')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('etat')
                    ->badge()
                    ->formatStateUsing(fn (EtatHistoire $state) => $state->libelle())
                    ->color(fn (EtatHistoire $state): string => match ($state) {
                        EtatHistoire::Brouillon => 'gray',
                        EtatHistoire::Relecture => 'warning',
                        EtatHistoire::Valide => 'info',
                        EtatHistoire::Publie => 'success',
                    }),
                // User n'a pas de colonne name : nom affiche par getFilamentName().
                Tables\Columns\TextColumn::make('auteur')
                    ->state(fn (Histoire $record): ?string => $record->user?->getFilamentName())
                    ->label('Auteur'),
                Tables\Columns\TextColumn::make('scenes_count')
                    ->label('Scènes')
                    ->counts('scenes'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('etat')
                    ->options(array_combine(
                        array_map(fn (EtatHistoire $etat) => $etat->value, EtatHistoire::cases()),
                        array_map(fn (EtatHistoire $etat) => $etat->libelle(), EtatHistoire::cases()),
                    )),
            ])
            ->actions([
                Tables\Actions\Action::make('scenes')
                    ->label('Scènes')
                    ->icon('heroicon-o-film')
                    ->color('gray')
                    ->url(fn (Histoire $record): string => static::getUrl('scenes', ['record' => $record]))
                    ->authorize('view'),
                Tables\Actions\Action::make('previsualiser')
                    ->label('Prévisualiser')
                    ->icon('heroicon-o-play')
                    ->color('gray')
                    ->url(fn (Histoire $record): string => static::getUrl('preview', ['record' => $record]))
                    ->authorize('view'),
                Tables\Actions\EditAction::make()
                    ->authorize('update'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // DeleteBulkAction ne controle pas chaque enregistrement : la policy est appliquee ici.
                    Tables\Actions\BulkAction::make('supprimer')
                        ->label('Supprimer la sélection')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records): void {
                            $utilisateur = auth()->user();
                            [$supprimables, $refusees] = $records->partition(fn (Histoire $histoire): bool => $utilisateur?->can('delete', $histoire) ?? false);

                            $supprimables->each->delete();

                            app(AuditService::class)->ecrire('Histoire.suppression_groupee', null, [
                                'supprimees' => $supprimables->values()->modelKeys(),
                                'refusees' => $refusees->values()->modelKeys(),
                            ]);

                            Notification::make()
                                ->title($supprimables->count().' histoire(s) supprimée(s)')
                                ->body($refusees->isNotEmpty() ? $refusees->count().' histoire(s) ne peuvent pas être supprimées dans leur état actuel.' : null)
                                ->color($refusees->isNotEmpty() ? 'warning' : 'success')
                                ->send();
                        }),
                ]),
            ]);
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\EditHistoire::class,
            Pages\ScenesHistoire::class,
            Pages\PreviewHistoire::class,
        ]);
    }

    public static function actionVerifier(): Actions\Action
    {
        return Actions\Action::make('verifier')
            ->label('Vérifier l\'histoire')
            ->icon('heroicon-o-clipboard-document-check')
            ->color('gray')
            ->modalHeading('Vérification de l\'histoire')
            ->modalDescription('Points à contrôler avant de passer l\'histoire en relecture. Rien n\'est bloquant.')
            ->modalContent(fn (Histoire $record) => view('filament.resources.histoire-resource.verification', [
                'controles' => app(VerificationHistoireService::class)->controler($record),
                'histoire' => $record,
            ]))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Fermer');
    }

    public static function urlCorrection(Histoire $histoire, string $cle): string
    {
        return static::getUrl($cle === 'sous_themes' ? 'edit' : 'scenes', ['record' => $histoire]);
    }

    /**
     * Visible seulement dans une histoire : le menu est rendu au chargement de la page, quand la route porte le {record}.
     */
    public static function getNavigationItems(): array
    {
        $items = parent::getNavigationItems();
        $histoire = self::histoireOuverte();

        if ($histoire === null) {
            return $items;
        }

        $nbProblemes = count(app(VerificationHistoireService::class)->problemes($histoire));

        $items[] = NavigationItem::make('Vérifier')
            ->parentItem(static::getNavigationLabel())
            ->isActiveWhen(fn (): bool => false)
            ->badge($nbProblemes > 0 ? (string) $nbProblemes : '✓', $nbProblemes > 0 ? 'warning' : 'success')
            ->badgeTooltip($nbProblemes > 0 ? "{$nbProblemes} point(s) à vérifier" : 'Tout est en ordre')
            ->url('#verifier');

        return $items;
    }

    private static function histoireOuverte(): ?Histoire
    {
        $record = request()->route('record');

        if ($record === null || ! request()->routeIs(static::getRouteBaseName().'.*')) {
            return null;
        }

        return $record instanceof Histoire ? $record : static::getEloquentQuery()->find($record);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHistoires::route('/'),
            'create' => Pages\CreateHistoire::route('/create'),
            'edit' => Pages\EditHistoire::route('/{record}/edit'),
            'scenes' => Pages\ScenesHistoire::route('/{record}/scenes'),
            'preview' => Pages\PreviewHistoire::route('/{record}/preview'),
        ];
    }
}
