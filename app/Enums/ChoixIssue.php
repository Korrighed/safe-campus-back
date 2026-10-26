<?php

namespace App\Enums;

enum ChoixIssue: string
{
    case Favorable = 'favorable';
    case Defavorable = 'defavorable';

    public function libelle(): string
    {
        return match ($this) {
            self::Favorable => 'Bonne fin',
            self::Defavorable => 'Fin à risque (affiche l\'aide)',
        };
    }
}
