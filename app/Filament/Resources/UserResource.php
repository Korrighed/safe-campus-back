<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Services\SuppressionCompteService;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $modelLabel = 'utilisateur';

    protected static ?string $pluralModelLabel = 'utilisateurs';

    /**
     * Roles que l'admin peut attribuer. Visiteur exclu : compte technique de
     * l'API front, jamais un compte de personne.
     *
     * @return array<string, string>
     */
    public static function rolesAttribuables(): array
    {
        return collect([UserRole::Webmaster, UserRole::Redacteur, UserRole::Admin])
            ->mapWithKeys(fn (UserRole $role) => [$role->value => $role->libelle()])
            ->all();
    }

    /**
     * Le compte visiteur n'apparait jamais dans la gestion des utilisateurs.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where(fn (Builder $query) => $query
                ->whereNull('role')
                ->orWhere('role', '!=', UserRole::Visiteur->value));
    }

    /**
     * Nombre de demandes d'inscription en attente, a cote de l'entree du menu.
     */
    public static function getNavigationBadge(): ?string
    {
        $enAttente = User::whereNull('role')->count();

        return $enAttente > 0 ? (string) $enAttente : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Demandes d\'inscription en attente';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Identité')
                    ->description('Renseignée par la personne à l\'inscription, non modifiable ici.')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('prenom')
                                    ->label('Prénom')
                                    ->disabled(),
                                Forms\Components\TextInput::make('nom')
                                    ->disabled(),
                                Forms\Components\TextInput::make('email')
                                    ->disabled(),
                                Forms\Components\TextInput::make('telephone')
                                    ->label('Téléphone')
                                    ->disabled(),
                            ]),
                    ]),
                Forms\Components\Select::make('role')
                    ->label('Rôle')
                    ->options(self::rolesAttribuables())
                    // Le Select n'impose pas ses options cote serveur : sans
                    // ce `in`, une requete forgee pourrait poser 'visiteur'.
                    ->in(array_keys(self::rolesAttribuables()))
                    ->placeholder('Aucun — accès refusé')
                    ->helperText('Sans rôle, le compte ne peut pas se connecter. Retirer le rôle coupe l\'accès sans supprimer le compte.')
                    ->native(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('prenom')
                    ->label('Prénom')
                    ->searchable(),
                Tables\Columns\TextColumn::make('nom')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('telephone')
                    ->label('Téléphone'),
                Tables\Columns\TextColumn::make('role')
                    ->label('Rôle')
                    ->badge()
                    ->formatStateUsing(fn (?UserRole $state) => $state?->libelle())
                    ->placeholder('En attente')
                    ->color(fn (?UserRole $state) => match ($state) {
                        UserRole::Admin => 'danger',
                        null => 'warning',
                        default => 'primary',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Inscription')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\Filter::make('en_attente')
                    ->label('En attente de validation')
                    ->query(fn (Builder $query) => $query->whereNull('role')),
                Tables\Filters\SelectFilter::make('role')
                    ->label('Rôle')
                    ->options(self::rolesAttribuables()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                self::configurerSuppression(Tables\Actions\Action::make('supprimer')),
            ]);
    }

    /**
     * Refus d'une demande (compte sans role) ou suppression d'un compte actif,
     * partage entre la liste et la fiche. Si le compte a des histoires, un
     * autre redacteur doit les reprendre : sinon la cascade les effacerait.
     */
    public static function configurerSuppression(
        Tables\Actions\Action|Actions\Action $action,
        bool $retourALaListe = false,
    ): Tables\Actions\Action|Actions\Action {
        return $action
            ->label(fn (User $record) => $record->role === null ? 'Refuser' : 'Supprimer')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->authorize('delete')
            ->requiresConfirmation()
            ->modalHeading(fn (User $record) => $record->role === null ? 'Refuser la demande' : 'Supprimer le compte')
            ->modalDescription(fn (User $record) => $record->role === null
                ? 'Le compte en attente sera supprimé. La personne pourra refaire une demande.'
                : 'Le compte sera définitivement supprimé. Pour seulement couper l\'accès, retirez plutôt son rôle.')
            ->form(function (User $record): array {
                $service = app(SuppressionCompteService::class);
                $nombre = $service->nombreHistoires($record);

                if ($nombre === 0) {
                    return [];
                }

                $repreneurs = $service->repreneursPossibles($record);

                return [
                    Forms\Components\Select::make('repreneur_id')
                        ->label('Rédacteur qui reprend les histoires')
                        ->options($repreneurs)
                        ->required()
                        ->native(false)
                        ->helperText($repreneurs === []
                            ? 'Aucun autre rédacteur : attribuez d\'abord le rôle rédacteur à un compte.'
                            : "Ce compte est l'auteur de {$nombre} histoire(s), qui seraient supprimées sans repreneur."),
                ];
            })
            ->action(function (User $record, array $data, $action, $livewire) use ($retourALaListe): void {
                $etaitEnAttente = $record->role === null;

                try {
                    app(SuppressionCompteService::class)->supprimer($record, isset($data['repreneur_id']) ? (int) $data['repreneur_id'] : null);
                } catch (InvalidArgumentException $exception) {
                    Notification::make()->title($exception->getMessage())->danger()->send();
                    $action->halt();
                }

                Notification::make()
                    ->title($etaitEnAttente ? 'Demande refusée' : 'Compte supprimé')
                    ->success()
                    ->send();

                if ($retourALaListe) {
                    $livewire->redirect(self::getUrl('index'));
                }
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
