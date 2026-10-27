<?php

namespace App\Services;

use App\Models\Choix;
use App\Models\Scene;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Ecrit une ligne dans le canal `audit` (storage/logs/audit-AAAA-MM-JJ.log) :
 * qui, quoi, sur quoi, quand, depuis quelle IP. Jamais de secret.
 */
class AuditService
{
    /**
     * Attributs jamais ecrits dans les logs.
     */
    public const CHAMPS_SENSIBLES = ['password', 'remember_token'];

    /**
     * Contenu redige par les redacteurs : jamais ecrit dans les logs, ni en
     * valeur ni en libelle. Seuls l'identifiant et le nom des champs
     * modifies sont traces.
     *
     * @var array<class-string<Model>, list<string>>
     */
    public const CHAMPS_CONTENU = [
        Scene::class => ['titre', 'dialogue_text'],
        Choix::class => ['text_choix'],
    ];

    /**
     * Au-dela, une valeur texte est tronquee : les contenus longs (articles,
     * textes de scene) ne doivent pas faire exploser la taille des logs.
     */
    private const LONGUEUR_MAX = 200;

    /**
     * @param  array<string, mixed>  $details
     */
    public function ecrire(string $action, ?Model $cible = null, array $details = []): void
    {
        Log::channel('audit')->info($action, array_filter([
            'auteur' => $this->auteur(),
            'cible' => $cible ? $this->decrire($cible) : null,
            ...$details,
            'ip' => app()->runningInConsole() ? null : request()->ip(),
        ], fn ($valeur) => $valeur !== null));
    }

    /**
     * Sans $model, les champs de contenu ne sont pas retires : l'observer s'en
     * sert pour lister les noms des champs modifies, jamais pour ecrire leur valeur.
     *
     * @param  array<string, mixed>  $attributs
     * @return array<string, mixed>
     */
    public function nettoyer(array $attributs, ?Model $model = null): array
    {
        $attributs = array_diff_key($attributs, array_flip([...self::CHAMPS_SENSIBLES, 'created_at', 'updated_at']));

        if ($model !== null) {
            $attributs = array_diff_key($attributs, array_flip(self::CHAMPS_CONTENU[$model::class] ?? []));
        }

        return array_map(fn ($valeur) => $this->abreger($valeur), $attributs);
    }

    /**
     * @return array<string, mixed>
     */
    public function decrire(Model $model): array
    {
        // Contenu redige : aucun libelle derive de ses attributs.
        $masque = isset(self::CHAMPS_CONTENU[$model::class]);

        return array_filter([
            'type' => class_basename($model),
            'id' => $model->getKey(),
            // Libelle lisible : reste exploitable apres suppression de la ligne.
            'libelle' => $masque ? null : ($model->getAttribute('email')
                ?? $model->getAttribute('ref')
                ?? $model->getAttribute('titre')
                ?? $model->getAttribute('libelle')
                ?? $model->getAttribute('numero')),
        ], fn ($valeur) => $valeur !== null);
    }

    /**
     * @return array<string, mixed>|string
     */
    private function auteur(): array|string
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return app()->runningInConsole() ? 'console' : 'anonyme';
        }

        return ['id' => $user->id, 'email' => $user->email, 'role' => $user->role?->value];
    }

    private function abreger(mixed $valeur): mixed
    {
        if ($valeur instanceof \BackedEnum) {
            return $valeur->value;
        }

        if (is_string($valeur) && mb_strlen($valeur) > self::LONGUEUR_MAX) {
            return Str::limit($valeur, self::LONGUEUR_MAX).' ('.mb_strlen($valeur).' caractères)';
        }

        return $valeur;
    }
}
