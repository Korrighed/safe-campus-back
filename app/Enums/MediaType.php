<?php

namespace App\Enums;

enum MediaType: string
{
    case Image = 'image';
    case Video = 'video';
    case Audio = 'audio';
    case Document = 'document';

    public function libelle(): string
    {
        return match ($this) {
            self::Image => 'Image',
            self::Video => 'Vidéo',
            self::Audio => 'Audio',
            self::Document => 'Document',
        };
    }

    /**
     * Liste blanche des types MIME acceptes au televersement, verifies sur le
     * contenu du fichier et non sur son extension. Ni SVG ni HTML : servis
     * depuis le domaine du back-office, ils peuvent embarquer du script.
     *
     * @return list<string>
     */
    public function typesMimeAcceptes(): array
    {
        return match ($this) {
            self::Image => ['image/png', 'image/jpeg', 'image/webp'],
            self::Video => ['video/mp4', 'video/webm'],
            self::Audio => ['audio/mpeg', 'audio/ogg'],
            self::Document => ['application/pdf'],
        };
    }

    /**
     * Formats acceptes, tels qu'affiches a l'utilisateur.
     */
    public function formatsAcceptes(): string
    {
        return match ($this) {
            self::Image => 'PNG, JPEG ou WebP',
            self::Video => 'MP4 ou WebM',
            self::Audio => 'MP3 ou OGG',
            self::Document => 'PDF',
        };
    }
}
