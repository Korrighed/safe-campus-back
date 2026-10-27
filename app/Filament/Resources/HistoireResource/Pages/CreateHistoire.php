<?php

namespace App\Filament\Resources\HistoireResource\Pages;

use App\Enums\EtatHistoire;
use App\Filament\Resources\HistoireResource;
use App\Models\Histoire;
use App\Models\Scene;
use Filament\Forms;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateHistoire extends CreateRecord
{
    use CreateRecord\Concerns\HasWizard;

    protected static string $resource = HistoireResource::class;

    protected function getSteps(): array
    {
        return [
            Forms\Components\Wizard\Step::make('L\'histoire')
                ->description('Titre et rubriques')
                ->schema([
                    HistoireResource::champTitre()
                        ->helperText('Visible par les joueurs une fois l\'histoire publiée. '.HistoireResource::mentionLimite(HistoireResource::TITRE_MAX)),
                    HistoireResource::champSousThemes(),
                ]),
            Forms\Components\Wizard\Step::make('La première scène')
                ->description('Point de départ du parcours')
                ->schema([
                    Forms\Components\TextInput::make('premiere_scene.titre')
                        ->label('Nom de la scène')
                        ->required()
                        ->maxLength(HistoireResource::TITRE_MAX)
                        ->default('Introduction')
                        ->helperText('Repère pour toi, jamais montré au joueur. '.HistoireResource::mentionLimite(HistoireResource::TITRE_MAX)),
                    Forms\Components\Textarea::make('premiere_scene.dialogue_text')
                        ->label('Mise en situation')
                        ->required()
                        ->rows(5)
                        ->maxLength(HistoireResource::DIALOGUE_MAX)
                        ->helperText('Le texte que lit le joueur en arrivant dans l\'histoire. Tu ajouteras les choix ensuite, depuis la page « 2. Scènes ». '.HistoireResource::mentionLimite(HistoireResource::DIALOGUE_MAX)),
                ]),
        ];
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data): Histoire {
            $histoire = Histoire::create([
                'titre' => $data['titre'],
                'etat' => EtatHistoire::Brouillon,
                'user_id' => auth()->id(),
            ]);

            $scene = Scene::create($data['premiere_scene']);
            $histoire->scenes()->attach($scene->id, ['est_initiale' => true, 'ordre' => 1]);

            return $histoire;
        });
    }

    protected function getRedirectUrl(): string
    {
        return HistoireResource::getUrl('scenes', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Histoire créée — ajoute maintenant les choix de la première scène.';
    }
}
