<?php

namespace App\Models;

use App\Enums\TelephoneType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property TelephoneType $type
 */
class Telephone extends Model
{
    use HasFactory;

    protected $fillable = [
        'numero',
        'numero_vert',
        'type',
        'libelle',
        'contact_id',
        'actif',
    ];

    protected function casts(): array
    {
        return [
            'type' => TelephoneType::class,
            'numero_vert' => 'boolean',
            'actif' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Telephones publiables. Un numero inactif reste en base mais disparait
     * du front.
     *
     * @param  Builder<Telephone>  $query
     */
    public function scopeActif(Builder $query): void
    {
        $query->where('actif', true);
    }
}
