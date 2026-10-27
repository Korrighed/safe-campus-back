<?php

namespace App\Filament\Resources;

use App\Enums\TelephoneType;
use App\Filament\Forms\Components\MapField;
use App\Filament\Resources\ContactResource\Pages;
use App\Filament\Resources\ContactResource\RelationManagers\SousThemesRelationManager;
use App\Filament\Resources\ContactResource\RelationManagers\TelephonesRelationManager;
use App\Models\Contact;
use App\Services\GeocodingService;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class ContactResource extends Resource
{
    protected static ?string $model = Contact::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                self::sectionDivider('Identité'),
                Forms\Components\Toggle::make('actif')
                    ->required()
                    ->default(true),
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('nom')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('prenom')
                            ->maxLength(255),
                    ]),
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Select::make('gratuit')
                            ->label('Gratuit')
                            ->options(['1' => 'Oui', '0' => 'Non'])
                            ->helperText('Non renseigné = inconnu, distinct de "Non".')
                            ->native(false),
                        Forms\Components\Select::make('anonyme')
                            ->label('Anonyme')
                            ->options(['1' => 'Oui', '0' => 'Non'])
                            ->helperText('Non renseigné = inconnu, distinct de "Non".')
                            ->native(false),
                    ]),

                self::sectionDivider('Location'),
                Forms\Components\Toggle::make('position_territoire')
                    ->label('Couvre tout le territoire')
                    ->helperText('Structure sans implantation ponctuelle (numero national, dispositif NC entier...). Aucune coordonnee ne sera enregistree.')
                    ->live()
                    ->afterStateUpdated(function (Set $set, bool $state): void {
                        if ($state) {
                            $set('latitude', null);
                            $set('longitude', null);
                        }
                    })
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('localisation')
                    ->maxLength(255)
                    ->columnSpanFull()
                    ->suffixActions([
                        Action::make('geocoder')
                            ->label('Géocoder')
                            ->icon('heroicon-o-map-pin')
                            ->visible(fn (Get $get): bool => ! $get('position_territoire'))
                            ->action(function (Get $get, Set $set, GeocodingService $geocodingService): void {
                                $address = $get('localisation');

                                if (blank($address)) {
                                    Notification::make()
                                        ->title('Renseigne une localisation avant de géocoder.')
                                        ->warning()
                                        ->send();

                                    return;
                                }

                                $coordinates = $geocodingService->geocode($address);

                                if ($coordinates === null) {
                                    Notification::make()
                                        ->title('Adresse introuvable.')
                                        ->body('Ajuste le pin manuellement sur la carte ou saisis directement les coordonnées.')
                                        ->danger()
                                        ->send();

                                    return;
                                }

                                $set('latitude', $coordinates['latitude']);
                                $set('longitude', $coordinates['longitude']);

                                Notification::make()
                                    ->title('Coordonnées mises à jour.')
                                    ->success()
                                    ->send();
                            }),
                        Action::make('effacer_position')
                            ->label('Effacer')
                            ->icon('heroicon-o-x-circle')
                            ->color('danger')
                            ->visible(fn (Get $get): bool => ! $get('position_territoire'))
                            ->action(function (Set $set): void {
                                $set('latitude', null);
                                $set('longitude', null);

                                Notification::make()
                                    ->title('Position vidée du formulaire.')
                                    ->body('Pense à cliquer sur Enregistrer pour appliquer le changement.')
                                    ->warning()
                                    ->send();
                            }),
                    ]),
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('longitude')
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->dehydratedWhenHidden()
                            ->helperText('Calculée automatiquement, non modifiable à la main.'),
                        Forms\Components\TextInput::make('latitude')
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->dehydratedWhenHidden()
                            ->helperText('Calculée automatiquement, non modifiable à la main.'),
                    ])
                    ->visible(fn (Get $get): bool => ! $get('position_territoire'))
                    ->dehydratedWhenHidden(),
                Forms\Components\Placeholder::make('map_hint')
                    ->hiddenLabel()
                    ->content(new HtmlString(
                        '<p class="flex items-center gap-2 text-base font-semibold text-gray-700 dark:text-gray-200">'
                        .'📍 Glisse le pin sur la carte pour corriger la position'
                        .'</p>'
                    ))
                    ->columnSpanFull()
                    ->visible(fn (Get $get): bool => ! $get('position_territoire')),
                MapField::make('carte')
                    ->label('Carte')
                    ->visible(fn (Get $get): bool => ! $get('position_territoire')),

                self::sectionDivider('Contacts'),
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('mail')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('site_web')
                            ->maxLength(255),
                    ]),
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('horaires')
                            ->maxLength(255),
                        Forms\Components\Repeater::make('telephones')
                            ->relationship()
                            ->label('Téléphones')
                            ->defaultItems(0)
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('numero')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\Select::make('type')
                                            ->options(array_combine(
                                                array_map(fn (TelephoneType $type) => $type->value, TelephoneType::cases()),
                                                array_map(fn (TelephoneType $type) => $type->libelle(), TelephoneType::cases()),
                                            ))
                                            ->required()
                                            ->native(false),
                                    ]),
                                Forms\Components\TextInput::make('libelle')
                                    ->helperText('Distingue plusieurs numéros d\'une même structure (ex. "Psychologue").')
                                    ->maxLength(255),
                                Forms\Components\Toggle::make('numero_vert')
                                    ->label('Numéro vert (gratuit depuis un fixe)'),
                            ])
                            ->itemLabel(fn (array $state): ?string => $state['numero'] ?? null)
                            ->addActionLabel('Ajouter un numéro')
                            ->collapsed(),
                    ]),

                Forms\Components\Textarea::make('remarques')
                    ->helperText('Public visé et conditions d\'accueil (ex. "réservé aux moins de 25 ans").')
                    ->columnSpanFull(),
            ]);
    }

    private static function sectionDivider(string $label): Forms\Components\Placeholder
    {
        return Forms\Components\Placeholder::make($label.'_divider')
            ->hiddenLabel()
            ->content(new HtmlString(
                '<div class="flex items-center gap-3 pt-1">'
                .'<span class="text-sm font-medium text-gray-500 dark:text-gray-400">'.e($label).'</span>'
                .'<hr class="flex-1 border-gray-200 dark:border-gray-700" />'
                .'</div>'
            ))
            ->columnSpanFull();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nom')
                    ->searchable(),
                Tables\Columns\TextColumn::make('mail')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('localisation')
                    ->searchable(),
                Tables\Columns\TextColumn::make('telephones.numero')
                    ->label('Téléphones')
                    ->listWithLineBreaks(),
                Tables\Columns\TextColumn::make('position')
                    ->label('Position')
                    ->badge()
                    ->getStateUsing(fn (Contact $record) => match (true) {
                        $record->position_territoire => 'Tout le territoire',
                        $record->latitude !== null && $record->longitude !== null => 'Géocodé',
                        default => 'Non renseigné',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Tout le territoire' => 'info',
                        'Géocodé' => 'success',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('site_web')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('horaires')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('actif')
                    ->boolean(),
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
                Tables\Filters\TernaryFilter::make('actif'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            TelephonesRelationManager::class,
            SousThemesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContacts::route('/'),
            'create' => Pages\CreateContact::route('/create'),
            'edit' => Pages\EditContact::route('/{record}/edit'),
        ];
    }
}
