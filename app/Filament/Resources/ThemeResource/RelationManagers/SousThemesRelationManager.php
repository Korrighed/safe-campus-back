<?php

namespace App\Filament\Resources\ThemeResource\RelationManagers;

use App\Models\SousTheme;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SousThemesRelationManager extends RelationManager
{
    protected static string $relationship = 'sousThemes';

    protected static ?string $title = 'Sous-thèmes';

    public function form(Form $form): Form
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
                Forms\Components\Toggle::make('permet_signalement')
                    ->label('Formulaire de signalement'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('libelle')
            ->recordAction(Tables\Actions\EditAction::class)
            ->columns([
                Tables\Columns\TextColumn::make('ref')
                    ->searchable(),
                Tables\Columns\TextColumn::make('libelle')
                    ->searchable()
                    ->tooltip('Une thématique précise rattachée à ce thème, par exemple « Alcool ».'),
                Tables\Columns\IconColumn::make('permet_signalement')
                    ->boolean()
                    ->label('Signalement')
                    ->falseIcon('heroicon-o-minus')
                    ->falseColor('gray'),
                Tables\Columns\TextColumn::make('contacts_count')
                    ->label('Contacts')
                    ->counts('contacts'),
                Tables\Columns\IconColumn::make('actif')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('actif'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
