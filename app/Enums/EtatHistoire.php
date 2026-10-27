<?php

namespace App\Enums;

enum EtatHistoire: string
{
    case Brouillon = 'brouillon';
    case Relecture = 'relecture';
    case Valide = 'valide';
    case Publie = 'publie';

    public function libelle(): string
    {
        return match ($this) {
            self::Brouillon => 'Brouillon',
            self::Relecture => 'Relecture',
            self::Valide => 'Validé',
            self::Publie => 'Publié',
        };
    }
}
