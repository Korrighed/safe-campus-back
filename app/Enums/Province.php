<?php

namespace App\Enums;

enum Province: string
{
    case Sud = 'p-sud';
    case Nord = 'p-nord';
    case Iles = 'p-iles';

    public function libelle(): string
    {
        return match ($this) {
            self::Sud => 'Province Sud',
            self::Nord => 'Province Nord',
            self::Iles => 'Province des Iles Loyaute',
        };
    }
}
