<?php

namespace App\Livewire;

use App\Filament\Resources\SousThemeResource;
use App\Models\SousTheme;
use App\Models\Theme;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ThemeSousThemesTable extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public Theme $theme;

    public function table(Table $table): Table
    {
        return $table
            ->query(SousTheme::query()->where('theme_id', $this->theme->id))
            ->recordTitleAttribute('libelle')
            ->recordUrl(fn (SousTheme $record): string => SousThemeResource::getUrl('edit', ['record' => $record]))
            ->columns([
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
            ])
            ->defaultSort('ordre')
            ->filters([
                Tables\Filters\TernaryFilter::make('permet_signalement'),
                Tables\Filters\TernaryFilter::make('actif'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public function render(): View
    {
        return view('livewire.theme-sous-themes-table');
    }
}
