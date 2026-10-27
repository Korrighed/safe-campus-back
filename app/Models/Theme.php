<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Theme extends Model
{
    use HasFactory;

    protected $fillable = [
        'ref',
        'libelle',
        'libelle_court',
        'resume',
        'ordre',
        'actif',
    ];

    protected function casts(): array
    {
        return [
            'actif' => 'boolean',
        ];
    }

    /**
     * @return HasMany<SousTheme, $this>
     */
    public function sousThemes(): HasMany
    {
        return $this->hasMany(SousTheme::class)->orderBy('ordre');
    }

    /**
     * @param  Builder<Theme>  $query
     */
    public function scopeOrdonne(Builder $query): void
    {
        $query->orderBy('ordre')->orderBy('id');
    }

    /**
     * Themes publiables. Un theme inactif reste en base mais disparait du front.
     */
    public function scopeActif(Builder $query): void
    {
        $query->where('actif', true);
    }

    /**
     * @return BelongsToMany<Media, $this>
     */
    public function medias(): BelongsToMany
    {
        return $this->belongsToMany(Media::class);
    }
}
