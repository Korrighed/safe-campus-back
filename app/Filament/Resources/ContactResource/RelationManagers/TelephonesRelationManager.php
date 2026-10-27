<?php

namespace App\Filament\Resources\ContactResource\RelationManagers;

use App\Enums\TelephoneType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class TelephonesRelationManager extends RelationManager
{
    protected static string $relationship = 'telephones';

    protected static ?string $title = 'Téléphones';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('numero')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Toggle::make('actif')
                            ->required()
                            ->default(true),
                    ]),
                Forms\Components\Select::make('type')
                    ->options(array_combine(
                        array_map(fn (TelephoneType $type) => $type->value, TelephoneType::cases()),
                        array_map(fn (TelephoneType $type) => $type->libelle(), TelephoneType::cases()),
                    ))
                    ->required()
                    ->native(false),
                Forms\Components\TextInput::make('libelle')
                    ->helperText('Distingue plusieurs numéros d\'une même structure (ex. "Psychologue").')
                    ->maxLength(255),
                Forms\Components\Toggle::make('numero_vert')
                    ->label('Numéro vert (gratuit depuis un fixe)'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('numero')
            ->recordAction(Tables\Actions\EditAction::class)
            ->columns([
                Tables\Columns\TextColumn::make('numero'),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (TelephoneType $state) => $state->libelle()),
                Tables\Columns\TextColumn::make('libelle'),
                Tables\Columns\IconColumn::make('numero_vert')
                    ->boolean()
                    ->label('Vert'),
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
