<?php

namespace App\Models;

use App\Enums\EtatHistoire;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * @property EtatHistoire $etat
 */
class Histoire extends Model
{
    use HasFactory;

    protected $fillable = [
        'ref',
        'titre',
        'etat',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'etat' => EtatHistoire::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Histoire $histoire): void {
            if (filled($histoire->ref)) {
                return;
            }

            $histoire->ref = self::genererRefUnique($histoire->titre);
        });
    }

    /**
     * Cle d'adressage par le front, invisible pour un redacteur qui cree une
     * histoire depuis Filament : deduite du titre, deduplique par suffixe si besoin.
     */
    private static function genererRefUnique(string $titre): string
    {
        $base = Str::slug($titre, '_') ?: 'histoire';
        $ref = $base;
        $suffixe = 2;

        while (static::where('ref', $ref)->exists()) {
            $ref = "{$base}_{$suffixe}";
            $suffixe++;
        }

        return $ref;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsToMany<SousTheme, $this>
     */
    public function sousThemes(): BelongsToMany
    {
        return $this->belongsToMany(SousTheme::class);
    }

    /**
     * @return BelongsToMany<Scene, $this>
     */
    public function scenes(): BelongsToMany
    {
        return $this->belongsToMany(Scene::class)->withPivot('est_initiale', 'ordre');
    }

    /**
     * Scene de depart du parcours. Au plus une par construction (index unique
     * partiel en base) — voir docs/schema_bd.md § Scene et partage entre histoires.
     */
    public function sceneInitiale(): ?Scene
    {
        return $this->scenes()->wherePivot('est_initiale', true)->first();
    }

    /**
     * @param  Builder<Histoire>  $query
     */
    public function scopePubliee(Builder $query): void
    {
        $query->where('etat', EtatHistoire::Publie);
    }
}
