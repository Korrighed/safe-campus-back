<?php

namespace App\Models;

use App\Enums\ChoixIssue;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property ChoixIssue|null $issue
 */
class Choix extends Model
{
    use HasFactory;

    /**
     * L'inflecteur Laravel pluralise « Choix » en « Choixes ». La table est
     * nommee « choix », il faut donc la declarer explicitement.
     */
    protected $table = 'choix';

    protected $fillable = [
        'scene_id',
        'next_scene_id',
        'text_choix',
        'issue',
    ];

    protected function casts(): array
    {
        return [
            'issue' => ChoixIssue::class,
        ];
    }

    /**
     * @return BelongsTo<Scene, $this>
     */
    public function scene(): BelongsTo
    {
        return $this->belongsTo(Scene::class);
    }

    /**
     * Scene suivante. Null = sortie du parcours (voir `issue`).
     *
     * @return BelongsTo<Scene, $this>
     */
    public function nextScene(): BelongsTo
    {
        return $this->belongsTo(Scene::class, 'next_scene_id');
    }
}
