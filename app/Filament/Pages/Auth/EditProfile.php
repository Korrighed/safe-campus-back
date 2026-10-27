<?php

namespace App\Filament\Pages\Auth;

use App\Services\TelephoneService;
use Closure;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Auth\EditProfile as BaseEditProfile;
use Illuminate\Support\Facades\DB;

/**
 * "Mon espace" : chaque compte connecte y modifie ses propres informations
 * (identite, telephone, mot de passe) et consulte son role, en lecture seule
 * (attribue uniquement par un admin, voir UserResource).
 */
class EditProfile extends BaseEditProfile
{
    public static function getLabel(): string
    {
        return 'Mon espace';
    }

    /**
     * @return array<int | string, string | Form>
     */
    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        Grid::make(2)->schema([
                            $this->getPrenomFormComponent(),
                            $this->getNomFormComponent(),
                        ]),
                        $this->getEmailFormComponent(),
                        $this->getTelephoneFormComponent(),
                        $this->getRoleFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                    ])
                    ->operation('edit')
                    ->model($this->getUser())
                    ->statePath('data')
                    ->inlineLabel(! static::isSimple()),
            ),
        ];
    }

    protected function getPrenomFormComponent(): Component
    {
        return TextInput::make('prenom')
            ->label('Prénom')
            ->required()
            ->maxLength(255)
            ->autofocus();
    }

    protected function getNomFormComponent(): Component
    {
        return TextInput::make('nom')
            ->label('Nom')
            ->required()
            ->maxLength(255);
    }

    /**
     * Meme verification que l'inscription (Register), en ignorant le compte
     * courant : comparaison insensible a la casse, Postgres ne le fait pas
     * nativement via ->unique().
     */
    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label(__('filament-panels::pages/auth/edit-profile.form.email.label'))
            ->email()
            ->required()
            ->maxLength(255)
            ->dehydrateStateUsing(fn (string $state): string => mb_strtolower(trim($state)))
            ->rule(function (): Closure {
                $idCourant = $this->getUser()->getKey();

                return function (string $attribute, mixed $value, Closure $fail) use ($idCourant) {
                    if (! is_string($value)) {
                        $fail('L\'adresse email est invalide.');

                        return;
                    }

                    $existe = DB::table('users')
                        ->whereRaw('lower(email) = ?', [mb_strtolower(trim($value))])
                        ->where('id', '!=', $idCourant)
                        ->exists();

                    if ($existe) {
                        $fail('Cette adresse email est déjà utilisée.');
                    }
                };
            });
    }

    protected function getTelephoneFormComponent(): Component
    {
        $telephones = app(TelephoneService::class);

        return TextInput::make('telephone')
            ->label('Téléphone')
            // Pas de ->tel() : sa regex generique, non traduite, ferait
            // doublon (message brut "validation.regex") avec la regle
            // ci-dessous, deja specifique et en francais.
            ->extraInputAttributes(['type' => 'tel'])
            ->required()
            ->placeholder('ex. 77 12 34')
            ->helperText('Numéro calédonien à 6 chiffres, +687 facultatif.')
            ->dehydrateStateUsing(fn (string $state): string => $telephones->normaliser($state))
            ->rule(fn () => function (string $attribute, mixed $value, Closure $fail) use ($telephones) {
                if (! is_string($value) || ! $telephones->estValide($value)) {
                    $fail('Le numéro doit être un numéro calédonien à 6 chiffres.');
                }
            });
    }

    /**
     * Lecture seule : le role est attribue par un admin (UserResource), pas
     * modifiable depuis l'espace personnel.
     */
    protected function getRoleFormComponent(): Component
    {
        return Placeholder::make('role')
            ->label('Rôle')
            ->content(fn (): string => $this->getUser()->role?->libelle() ?? 'Aucun — accès refusé');
    }

    protected function getPasswordFormComponent(): Component
    {
        $component = parent::getPasswordFormComponent();

        // @phpstan-ignore method.notFound
        return $component->helperText('14 caractères minimum, dont une majuscule, une minuscule et un caractère spécial. Laisser vide pour ne pas changer.');
    }
}
