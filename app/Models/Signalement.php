<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Module reporte. Le champ d'etat et la cible de routage ne sont pas tranches.
 */
class Signalement extends Model
{
    use HasFactory;

    protected $fillable = [
        'token_antispam',
        'sous_theme_id',
        'texte',
        'date_heure',
    ];

    protected function casts(): array
    {
        return [
            'date_heure' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<SousTheme, $this>
     */
    public function sousTheme(): BelongsTo
    {
        return $this->belongsTo(SousTheme::class);
    }
}
