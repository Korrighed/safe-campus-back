<?php

namespace App\Filament\Resources\SousThemeResource\RelationManagers;

use App\Models\Contact;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Attache/detache des contacts existants a un sous-theme et permet
 * d'activer/desactiver leur publication directement depuis la fiche. Le
 * cycle de vie du Contact (creation/edition/suppression) se fait via
 * ContactResource, pas ici.
 */
class ContactsRelationManager extends RelationManager
{
    protected static string $relationship = 'contacts';

    protected static ?string $title = 'Contacts';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nom')
            ->columns([
                Tables\Columns\TextColumn::make('nom')
                    ->searchable(),
                Tables\Columns\TextColumn::make('mail')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('localisation')
                    ->searchable(),
                Tables\Columns\TextColumn::make('pivot.ordre')
                    ->label('Ordre'),
                Tables\Columns\ToggleColumn::make('actif'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('actif'),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->recordTitleAttribute('nom')
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
                    ->fillForm(fn (Contact $record): array => ['ordre' => $record->pivot->getAttribute('ordre')])
                    ->action(function (Contact $record, array $data): void {
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
