<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            UserResource::configurerSuppression(Actions\Action::make('supprimer'), retourALaListe: true),
        ];
    }

    /**
     * Seul le role est modifiable : les champs d'identite sont desactives et
     * donc non envoyes, mais on filtre quand meme contre une requete forgee.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return ['role' => $data['role'] ?? null];
    }
}
