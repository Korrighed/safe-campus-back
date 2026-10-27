<?php

namespace App\Filament\Resources\ThemeResource\RelationManagers;

use App\Enums\MediaType;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Attache/detache des medias existants a un theme : texte, image, video ou
 * logo de presentation sur l'accueil (non exclusifs entre eux). Le cycle de
 * vie du Media (creation/edition/suppression) se fait via MediaResource, pas
 * ici. Pas d'ordre sur ce pivot (media_theme), contrairement a
 * media_sous_theme.
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
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->recordTitleAttribute('libelle'),
            ])
            ->actions([
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ]);
    }
}
