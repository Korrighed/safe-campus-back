<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SousThemeResource\Pages;
use App\Filament\Resources\SousThemeResource\RelationManagers\ContactsRelationManager;
use App\Filament\Resources\SousThemeResource\RelationManagers\MediasRelationManager;
use App\Models\SousTheme;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SousThemeResource extends Resource
{
    protected static ?string $model = SousTheme::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('libelle')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Toggle::make('actif')
                            ->required()
                            ->default(true),
                    ]),
                Forms\Components\Select::make('theme_id')
                    ->label('Thème')
                    ->relationship('theme', 'libelle')
                    ->required()
                    ->native(false),
                Forms\Components\TextInput::make('ref')
                    ->required()
                    ->maxLength(255)
                    ->helperText('Clé stable, jamais affichée. Non modifiable après création.')
                    ->disabled(fn (?SousTheme $record) => $record !== null)
                    ->dehydrated(fn (?SousTheme $record) => $record === null),
                Forms\Components\Textarea::make('resume')
                    ->helperText('Teaser affiché sur la carte d\'accueil.')
                    ->columnSpanFull(),
                Forms\Components\RichEditor::make('article')
                    ->helperText('Contenu éditorial complet de la fiche.')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('intro_ressources')
                    ->label('Introduction (page ressources)')
                    ->helperText('Chapeau de la page ressources, distinct du résumé et de l\'article.')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('ordre')
                    ->numeric()
                    ->default(0)
                    ->required()
                    ->helperText('Ordre d\'affichage dans le thème (0 = en tête).'),
                Forms\Components\Toggle::make('permet_signalement')
                    ->label('Formulaire de signalement')
                    ->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('theme.libelle')
                    ->label('Thème')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ref')
                    ->searchable(),
                Tables\Columns\TextColumn::make('libelle')
                    ->searchable(),
                Tables\Columns\TextColumn::make('ordre')
                    ->sortable(),
                Tables\Columns\IconColumn::make('permet_signalement')
                    ->label('Signalement')
                    ->boolean()
                    ->falseIcon('heroicon-o-minus')
                    ->falseColor('gray'),
                Tables\Columns\TextColumn::make('contacts_count')
                    ->label('Contacts')
                    ->counts('contacts'),
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
            ->defaultSort('theme.libelle')
            ->filters([
                Tables\Filters\SelectFilter::make('theme')
                    ->relationship('theme', 'libelle'),
                Tables\Filters\TernaryFilter::make('permet_signalement'),
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
            ContactsRelationManager::class,
            MediasRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSousThemes::route('/'),
            'create' => Pages\CreateSousTheme::route('/create'),
            'edit' => Pages\EditSousTheme::route('/{record}/edit'),
        ];
    }
}
