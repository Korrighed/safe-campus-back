<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ThemeResource\Pages;
use App\Filament\Resources\ThemeResource\RelationManagers\MediasRelationManager;
use App\Filament\Resources\ThemeResource\RelationManagers\SousThemesRelationManager;
use App\Models\Theme;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ThemeResource extends Resource
{
    protected static ?string $model = Theme::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('libelle')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Libellé long, affiché dans le menu et la recherche.'),
                        Forms\Components\Toggle::make('actif')
                            ->required()
                            ->default(true),
                    ]),
                Forms\Components\TextInput::make('ref')
                    ->required()
                    ->maxLength(255)
                    ->helperText('Clé stable, jamais affichée. Non modifiable après création.')
                    ->disabled(fn (?Theme $record) => $record !== null)
                    ->dehydrated(fn (?Theme $record) => $record === null),
                Forms\Components\TextInput::make('libelle_court')
                    ->maxLength(255)
                    ->helperText('Libellé court, affiché dans la navigation et les tags de fiche.'),
                Forms\Components\Textarea::make('resume')
                    ->helperText('Teaser optionnel, présentation du thème sur l\'accueil.')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('ordre')
                    ->numeric()
                    ->default(0)
                    ->required()
                    ->helperText('Ordre d\'affichage des sections sur l\'accueil (0 = en tête).'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ref')
                    ->searchable(),
                Tables\Columns\TextColumn::make('libelle')
                    ->searchable(),
                Tables\Columns\TextColumn::make('libelle_court')
                    ->label('Libellé court')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('ordre')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sous_themes_count')
                    ->label('Sous-thèmes')
                    ->counts('sousThemes'),
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
            ->defaultSort('ordre')
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
            SousThemesRelationManager::class,
            MediasRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListThemes::route('/'),
            'create' => Pages\CreateTheme::route('/create'),
            'edit' => Pages\EditTheme::route('/{record}/edit'),
        ];
    }
}
