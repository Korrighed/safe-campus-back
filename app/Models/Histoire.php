<?php

namespace App\Models;

use App\Enums\EtatHistoire;
use App\Enums\UserRole;
use App\Services\NettoyageScenesService;
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

    /**
     * Scenes de chaque histoire en cours de suppression, memorisees avant que
     * la cascade SQL ne detache le pivot. Cle : id de l'histoire.
     *
     * @var array<int, list<int>>
     */
    private static array $scenesANettoyer = [];

    protected static function booted(): void
    {
        static::creating(function (Histoire $histoire): void {
            if (filled($histoire->ref)) {
                return;
            }

            $histoire->ref = self::genererRefUnique($histoire->titre);
        });

        static::deleting(function (Histoire $histoire): void {
            self::$scenesANettoyer[$histoire->getKey()] = $histoire->scenes()->pluck('scenes.id')->map(fn ($id) => (int) $id)->all();
        });

        static::deleted(function (Histoire $histoire): void {
            $ids = self::$scenesANettoyer[$histoire->getKey()] ?? [];
            unset(self::$scenesANettoyer[$histoire->getKey()]);

            app(NettoyageScenesService::class)->supprimerOrphelinesParmi($ids);
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

    /**
     * @return BelongsTo<User, $this>
     */
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

    /**
     * Histoires que l'utilisateur peut voir : toutes pour un relecteur, les
     * siennes pour un redacteur, aucune pour les autres roles.
     *
     * @param  Builder<Histoire>  $query
     */
    public function scopeVisiblePar(Builder $query, ?User $user): void
    {
        if ($user?->role === UserRole::Relecteur) {
            return;
        }

        if ($user?->role === UserRole::Redacteur) {
            $query->where('histoires.user_id', $user->id);

            return;
        }

        $query->whereRaw('1 = 0');
    }
}
