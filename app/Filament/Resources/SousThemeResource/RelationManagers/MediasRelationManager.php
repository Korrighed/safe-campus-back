<?php

namespace App\Filament\Resources\SousThemeResource\RelationManagers;

use App\Enums\MediaType;
use App\Models\Media;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Attache/detache des medias existants a un sous-theme : illustrations et
 * fiches reflectives (documents) de la page ressources. Le cycle de vie du
 * Media (creation/edition/suppression) se fait via MediaResource, pas ici.
 */
class MediasRelationManager extends RelationManager
{
    protected static string $relationship = 'medias';

    protected static ?string $title = 'Médias';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('libelle')
            ->columns([
                Tables\Columns\TextColumn::make('libelle')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (MediaType $state) => $state->libelle()),
                Tables\Columns\TextColumn::make('pivot.ordre')
                    ->label('Ordre'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->recordTitleAttribute('libelle')
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect(),
                        Forms\Components\TextInput::make('ordre')
                            ->label('Ordre')
                            ->helperText('Ordre d\'affichage sur la fiche ressources (0 = en tête).')
                            ->numeric()
                            ->default(0)
                            ->required(),
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('ordre')
                    ->label('Ordre')
                    ->icon('heroicon-o-pencil-square')
                    ->form([
                        Forms\Components\TextInput::make('ordre')
                            ->numeric()
                            ->required(),
                    ])
                    ->fillForm(fn (Media $record): array => ['ordre' => $record->pivot->getAttribute('ordre')])
                    ->action(function (Media $record, array $data): void {
                        $record->pivot->update(['ordre' => $data['ordre']]);
                    }),
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ]);
    }
}
