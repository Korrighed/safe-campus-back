<?php

namespace App\Filament\Resources;

use App\Enums\MediaType;
use App\Filament\Resources\MediaResource\Pages;
use App\Filament\Resources\MediaResource\RelationManagers\SousThemesRelationManager;
use App\Models\Media;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MediaResource extends Resource
{
    protected static ?string $model = Media::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?int $navigationSort = 4;

    /**
     * Supports visuels (aplats, logos) : 10 Mo suffisent largement.
     */
    public const TAILLE_MAX_KO = 10240;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('libelle')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Clé de recherche du média dans les sélecteurs.'),
                        Forms\Components\Toggle::make('actif')
                            ->required()
                            ->default(true),
                    ]),
                Forms\Components\Textarea::make('description')
                    ->helperText('Texte affiché sous le titre d\'une fiche réflective. Inutile pour une illustration.')
                    ->columnSpanFull(),
                Forms\Components\Select::make('type')
                    ->options(array_combine(
                        array_map(fn (MediaType $type) => $type->value, MediaType::cases()),
                        array_map(fn (MediaType $type) => $type->libelle(), MediaType::cases()),
                    ))
                    ->required()
                    ->native(false)
                    ->live()
                    // Le type fixe le repertoire de stockage : le changer apres
                    // coup laisserait le fichier dans le repertoire d'un autre type.
                    ->disabledOn('edit')
                    ->helperText(fn (string $operation) => $operation === 'edit'
                        ? 'Non modifiable : pour changer de type, créez un nouveau média.'
                        : null),
                Forms\Components\FileUpload::make('chemin')
                    ->label('Fichier')
                    ->disk('public')
                    ->directory(fn (Get $get) => 'medias/'.($get('type') ?? MediaType::Document->value))
                    ->visibility('public')
                    // Sans type choisi, aucun format n'est accepte.
                    ->acceptedFileTypes(fn (Get $get) => MediaType::tryFrom((string) $get('type'))?->typesMimeAcceptes() ?? [])
                    ->maxSize(self::TAILLE_MAX_KO)
                    ->downloadable()
                    ->openable()
                    ->required()
                    ->helperText(fn (Get $get) => ($type = MediaType::tryFrom((string) $get('type')))
                        ? $type->formatsAcceptes().', 10 Mo maximum.'
                        : 'Choisissez d\'abord le type de média.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('libelle')
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (MediaType $state) => $state->libelle()),
                Tables\Columns\TextColumn::make('chemin')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('actif')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options(array_combine(
                        array_map(fn (MediaType $type) => $type->value, MediaType::cases()),
                        array_map(fn (MediaType $type) => $type->libelle(), MediaType::cases()),
                    )),
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
            SousThemesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMedia::route('/'),
            'create' => Pages\CreateMedia::route('/create'),
            'edit' => Pages\EditMedia::route('/{record}/edit'),
        ];
    }
}
