<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Validation\ValidationException;

/**
 * Distingue un compte en attente de validation (inscrit, sans role) d'un
 * echec d'identifiants : la page d'origine renvoie le meme message generique.
 * Le message d'attente n'apparait qu'apres un mot de passe correct, il ne
 * revele donc pas l'existence d'un email.
 */
class Login extends BaseLogin
{
    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $data = $this->form->getState();

        if (! Filament::auth()->attempt($this->getCredentialsFromFormData($data), $data['remember'] ?? false)) {
            $this->throwFailureValidationException();
        }

        $user = Filament::auth()->user();

        if (! $user->canAccessPanel(Filament::getCurrentPanel())) {
            Filament::auth()->logout();

            if ($user instanceof User && $user->role === null) {
                throw ValidationException::withMessages([
                    'data.email' => 'Votre compte est en attente de validation par un administrateur.',
                ]);
            }

            $this->throwFailureValidationException();
        }

        session()->regenerate();

        return app(LoginResponse::class);
    }

    /**
     * Les emails sont stockes en minuscules (voir Register) : JEAN@test.nc
     * doit ouvrir le compte jean@test.nc.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function getCredentialsFromFormData(array $data): array
    {
        return [
            'email' => mb_strtolower(trim($data['email'])),
            'password' => $data['password'],
        ];
    }
}
