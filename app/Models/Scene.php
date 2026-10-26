<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Titre est un libelle court, a usage back-office uniquement — il n'est pas
 * affiche au joueur. Sans lui, la liste des scenes et le selecteur de
 * next_scene_id n'affichent que du dialogue tronque.
 *
 * @property-read Pivot $pivot Présent quand la scène est lue via une histoire.
 */
class Scene extends Model
{
    use HasFactory;

    protected $fillable = [
        'titre',
        'dialogue_text',
        'sous_theme_id',
        'media_id',
    ];

    /**
     * @return BelongsTo<SousTheme, $this>
     */
    public function sousTheme(): BelongsTo
    {
        return $this->belongsTo(SousTheme::class);
    }

    /**
     * @return BelongsTo<Media, $this>
     */
    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    /**
     * @return HasMany<Choix, $this>
     */
    public function choix(): HasMany
    {
        return $this->hasMany(Choix::class);
    }

    /**
     * Choix d'autres scenes qui menent vers celle-ci.
     *
     * @return HasMany<Choix, $this>
     */
    public function choixEntrants(): HasMany
    {
        return $this->hasMany(Choix::class, 'next_scene_id');
    }

    /**
     * Une scene peut appartenir a plusieurs histoires — raison d'etre de
     * Liaison_Histoire_Scene, reutilisation sans duplication de contenu.
     *
     * @return BelongsToMany<Histoire, $this>
     */
    public function histoires(): BelongsToMany
    {
        return $this->belongsToMany(Histoire::class)->withPivot('est_initiale', 'ordre');
    }
}
