<?php

namespace App\Enums;

enum UserRole: string
{
    case Webmaster = 'webmaster';
    case Redacteur = 'redacteur';
    case Relecteur = 'relecteur';
    case Visiteur = 'visiteur';
    case Admin = 'admin';

    public function libelle(): string
    {
        return match ($this) {
            self::Webmaster => 'Webmaster',
            self::Redacteur => 'Rédacteur',
            self::Relecteur => 'Relecteur',
            self::Visiteur => 'Visiteur',
            self::Admin => 'Administrateur',
        };
    }
}
