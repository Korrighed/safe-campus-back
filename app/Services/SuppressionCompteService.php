<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Histoire;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Suppression d'un compte par l'admin, ou purge automatique des comptes
 * inactifs. histoires.user_id est en cascadeOnDelete : sans transfert
 * prealable, supprimer un redacteur effacerait ses histoires, leurs scenes
 * et leurs choix.
 */
class SuppressionCompteService
{
    /**
     * Duree sans connexion au-dela de laquelle un compte est purge.
     */
    public const INACTIVITE_ANNEES = 3;

    public function __construct(private readonly AuditService $audit) {}

    /**
     * Supprime les comptes sans connexion depuis INACTIVITE_ANNEES ans (un
     * compte jamais connecte est juge sur sa date d'inscription).
     *
     * Jamais purges : le visiteur (compte technique) et les admins (le dernier
     * ne doit pas disparaitre ; un admin parti se supprime a la main). Un
     * compte qui a des histoires est ignore : le repreneur ne peut etre choisi
     * que par un admin, sinon la cascade effacerait les histoires.
     *
     * @return array{supprimes: list<string>, ignores: list<string>} emails
     */
    public function purgerInactifs(bool $simulation = false): array
    {
        $comptes = User::query()
            ->where(fn (Builder $query) => $query
                ->whereNull('role')
                ->orWhereNotIn('role', [UserRole::Visiteur->value, UserRole::Admin->value]))
            ->whereRaw('COALESCE(derniere_connexion_at, created_at) < ?', [now()->subYears(self::INACTIVITE_ANNEES)])
            ->get();

        $resultat = ['supprimes' => [], 'ignores' => []];

        foreach ($comptes as $compte) {
            if ($this->nombreHistoires($compte) > 0) {
                $resultat['ignores'][] = $compte->email;
                $this->audit->ecrire('User.purge_ignoree', $compte, ['motif' => 'histoires_a_transferer']);

                continue;
            }

            $resultat['supprimes'][] = $compte->email;

            if ($simulation) {
                continue;
            }

            // Trace avant suppression : l'observer ecrit ensuite User.supprime.
            $this->audit->ecrire('User.purge_inactivite', $compte, ['inactivite_annees' => self::INACTIVITE_ANNEES]);
            $compte->delete();
        }

        return $resultat;
    }

    /**
     * Redacteurs pouvant reprendre les histoires du compte supprime.
     *
     * @return array<int, string>
     */
    public function repreneursPossibles(User $compte): array
    {
        return User::where('role', UserRole::Redacteur->value)
            ->whereKeyNot($compte->getKey())
            ->orderBy('nom')
            ->get()
            ->mapWithKeys(fn (User $user) => [$user->id => trim("{$user->prenom} {$user->nom}")." ({$user->email})"])
            ->all();
    }

    public function nombreHistoires(User $compte): int
    {
        return Histoire::where('user_id', $compte->getKey())->count();
    }

    /**
     * @throws InvalidArgumentException si le compte a des histoires et que le
     *                                  repreneur est absent ou invalide.
     */
    public function supprimer(User $compte, ?int $repreneurId = null): void
    {
        DB::transaction(function () use ($compte, $repreneurId): void {
            $histoires = Histoire::where('user_id', $compte->getKey())->get();

            if ($histoires->isNotEmpty()) {
                $repreneur = $repreneurId !== null
                    ? User::whereKey($repreneurId)->where('role', UserRole::Redacteur->value)->first()
                    : null;

                if ($repreneur === null || $repreneur->is($compte)) {
                    throw new InvalidArgumentException('Un autre rédacteur doit reprendre les histoires de ce compte.');
                }

                // Une par une (et non update() en masse) : chaque transfert
                // passe par l'observer et apparait dans les logs d'audit.
                foreach ($histoires as $histoire) {
                    $histoire->user_id = $repreneur->id;
                    $histoire->save();
                }

                $cible = $this->audit->decrire($compte);
                $details = ['repreneur' => $this->audit->decrire($repreneur), 'nombre' => $histoires->count()];

                // Comme l'observer : ecrit seulement si la transaction aboutit.
                DB::afterCommit(fn () => $this->audit->ecrire('User.histoires_transferees', null, ['cible' => $cible, ...$details]));
            }

            $compte->delete();
        });
    }
}
