<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;

/**
 * `role` est casté en UserRole (voir casts()) ; null tant qu'un administrateur
 * n'a pas validé l'inscription.
 *
 * @property UserRole|null $role
 * @property Carbon|null $email_verified_at
 * @property Carbon|null $derniere_connexion_at
 */
class User extends Authenticatable implements FilamentUser, HasName
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nom',
        'prenom',
        'email',
        'telephone',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'derniere_connexion_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    /**
     * Sanctum authentifie l'API, il ne gouverne pas l'acces au panel.
     * Allowlist explicite : le role Visiteur (auth front) ne doit jamais
     * donner acces a /admin. L'admin entre dans le panel mais ne voit que
     * la gestion des utilisateurs (UserPolicy). Un compte sans role (inscription en attente de
     * validation) est refuse des la page de connexion — voir
     * App\Filament\Pages\Auth\Login.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return in_array($this->role, [UserRole::Webmaster, UserRole::Redacteur, UserRole::Admin], true);
    }

    /**
     * Nom affiche par Filament (menu utilisateur) : il n'y a plus de `name`.
     */
    public function getFilamentName(): string
    {
        return trim("{$this->prenom} {$this->nom}");
    }
}
