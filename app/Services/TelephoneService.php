<?php

namespace App\Services;

class TelephoneService
{
    /**
     * Mobile ou fixe caledonien : 6 chiffres commencant par 2 a 9, prefixe
     * +687 / 00687 facultatif. Espaces, points et tirets toleres a la saisie.
     */
    public const REGEX_NC = '/^(?:(?:\+|00)687)?[2-9]\d{5}$/';

    public function nettoyer(string $saisie): string
    {
        return preg_replace('/[\s.\-]/', '', $saisie);
    }

    public function estValide(string $saisie): bool
    {
        return (bool) preg_match(self::REGEX_NC, $this->nettoyer($saisie));
    }

    /**
     * Format de stockage E.164 : +687XXXXXX.
     */
    public function normaliser(string $saisie): string
    {
        return '+687'.substr($this->nettoyer($saisie), -6);
    }
}
