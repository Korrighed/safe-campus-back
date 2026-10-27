<?php

namespace App\Models;

use App\Enums\MediaType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Le sous-theme est la fiche : « libelle » est le titre affiche, « article »
 * le contenu editorial. Il n'existe pas de table Fiche separee.
 *
 * @property-read Pivot $pivot Présent quand le sous-thème est lu via une relation N-N.
 */
class SousTheme extends Model
{
    use HasFactory;

    protected $fillable = [
        'ref',
        'libelle',
        'resume',
        'article',
        'intro_ressources',
        'theme_id',
        'permet_signalement',
        'ordre',
        'actif',
    ];

    protected function casts(): array
    {
        return [
            'permet_signalement' => 'boolean',
            'actif' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Theme, $this>
     */
    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }

    /**
     * Sous-themes publiables. Un sous-theme inactif reste en base mais
     * disparait du front.
     *
     * @param  Builder<SousTheme>  $query
     */
    public function scopeActif(Builder $query): void
    {
        $query->where('actif', true);
    }

    /**
     * @return HasMany<Signalement, $this>
     */
    public function signalements(): HasMany
    {
        return $this->hasMany(Signalement::class);
    }

    /**
     * @return BelongsToMany<Contact, $this>
     */
    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class)
            ->withPivot('ordre')
            ->orderByPivot('ordre');
    }

    /**
     * @return BelongsToMany<Histoire, $this>
     */
    public function histoires(): BelongsToMany
    {
        return $this->belongsToMany(Histoire::class);
    }

    /**
     * @return BelongsToMany<Media, $this>
     */
    public function medias(): BelongsToMany
    {
        return $this->belongsToMany(Media::class)
            ->withPivot('ordre')
            ->orderByPivot('ordre');
    }

    /**
     * @return BelongsToMany<Media, $this>
     */
    public function documents(): BelongsToMany
    {
        return $this->medias()->where('type', MediaType::Document);
    }
}
