<?php

namespace App\Models;

use App\Enums\Province;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Str;

/**
 * @property-read Pivot $pivot Présent quand le contact est lu via une relation N-N.
 */
class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'ref',
        'nom',
        'prenom',
        'mail',
        'localisation',
        'site_web',
        'horaires',
        'remarques',
        'gratuit',
        'anonyme',
        'actif',
        'latitude',
        'longitude',
        'position_territoire',
        'province',
    ];

    protected function casts(): array
    {
        return [
            'gratuit' => 'boolean',
            'anonyme' => 'boolean',
            'actif' => 'boolean',
            'latitude' => 'float',
            'longitude' => 'float',
            'position_territoire' => 'boolean',
            'province' => Province::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Contact $contact): void {
            if (filled($contact->ref)) {
                return;
            }

            $contact->ref = self::genererRefUnique($contact->nom);
        });
    }

    /**
     * Cle de dedoublonnage du seeder, invisible pour un webmaster qui cree un
     * contact depuis Filament : deduite du nom, deduplique par suffixe si besoin.
     */
    private static function genererRefUnique(string $nom): string
    {
        $base = Str::slug($nom, '_') ?: 'contact';
        $ref = $base;
        $suffixe = 2;

        while (static::where('ref', $ref)->exists()) {
            $ref = "{$base}_{$suffixe}";
            $suffixe++;
        }

        return $ref;
    }

    /**
     * @return HasMany<Telephone, $this>
     */
    public function telephones(): HasMany
    {
        return $this->hasMany(Telephone::class);
    }

    /**
     * @return BelongsToMany<SousTheme, $this>
     */
    public function sousThemes(): BelongsToMany
    {
        return $this->belongsToMany(SousTheme::class)->withPivot('ordre');
    }

    /**
     * Contacts publiables. Un contact inactif reste en base mais disparait du front.
     */
    public function scopeActif(Builder $query): void
    {
        $query->where('actif', true);
    }
}
