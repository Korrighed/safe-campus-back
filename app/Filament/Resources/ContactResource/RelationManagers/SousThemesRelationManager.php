<?php

namespace App\Filament\Resources\ContactResource\RelationManagers;

use App\Models\SousTheme;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Attache/detache des sous-themes existants a un contact. Le cycle de vie
 * du SousTheme (creation/edition/suppression) se fait via
 * ThemeResource\RelationManagers\SousThemesRelationManager, pas ici.
 */
class SousThemesRelationManager extends RelationManager
{
    protected static string $relationship = 'sousThemes';

    protected static ?string $title = 'Sous-thèmes';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('libelle')
            ->columns([
                Tables\Columns\TextColumn::make('libelle')
                    ->searchable(),
                Tables\Columns\TextColumn::make('theme.libelle')
                    ->label('Thème'),
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
                            ->helperText('Priorité éditoriale d\'affichage sur la fiche (0 = en tête).')
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
                    ->fillForm(fn (SousTheme $record): array => ['ordre' => $record->pivot->getAttribute('ordre')])
                    ->action(function (SousTheme $record, array $data): void {
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
