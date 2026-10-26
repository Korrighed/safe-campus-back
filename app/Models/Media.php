<?php

namespace App\Models;

use App\Enums\MediaType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Facades\Storage;

/**
 * @property MediaType $type
 * @property-read string|null $url Accesseur url() : URL publique du fichier.
 * @property-read Pivot $pivot Présent quand le média est lu via une relation N-N.
 */
class Media extends Model
{
    use HasFactory;

    /**
     * L'inflecteur Laravel rend « media » (deja pluriel en anglais). La table
     * est nommee « medias », il faut donc la declarer explicitement.
     */
    protected $table = 'medias';

    protected $fillable = [
        'libelle',
        'description',
        'chemin',
        'type',
        'actif',
    ];

    protected function casts(): array
    {
        return [
            'type' => MediaType::class,
            'actif' => 'boolean',
        ];
    }

    /**
     * `chemin` est relatif au disque `public` (voir config/filesystems.php) —
     * cet accesseur evite au front de connaitre la convention de stockage.
     */
    protected function url(): Attribute
    {
        return Attribute::get(
            fn () => $this->chemin ? Storage::disk('public')->url($this->chemin) : null
        );
    }

    /**
     * @return BelongsToMany<SousTheme, $this>
     */
    public function sousThemes(): BelongsToMany
    {
        return $this->belongsToMany(SousTheme::class)->withPivot('ordre');
    }

    /**
     * @return BelongsToMany<Theme, $this>
     */
    public function themes(): BelongsToMany
    {
        return $this->belongsToMany(Theme::class);
    }

    /**
     * Medias publiables. Un media inactif reste en base mais disparait du front.
     *
     * @param  Builder<Media>  $query
     */
    public function scopeActif(Builder $query): void
    {
        $query->where('actif', true);
    }
}
