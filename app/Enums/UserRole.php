<?php

namespace App\Enums;

enum UserRole: string
{
    case Webmaster = 'webmaster';
    case Redacteur = 'redacteur';
    case Visiteur = 'visiteur';
    case Admin = 'admin';

    public function libelle(): string
    {
        return match ($this) {
            self::Webmaster => 'Webmaster',
            self::Redacteur => 'Rédacteur',
            self::Visiteur => 'Visiteur',
            self::Admin => 'Administrateur',
        };
    }
}
