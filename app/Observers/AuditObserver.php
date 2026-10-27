<?php

namespace App\Observers;

use App\Services\AuditService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Database\Eloquent\Model;

/**
 * Trace toute creation, modification et suppression d'un modele observe —
 * branche sur les modeles dans AppServiceProvider::registerAudit().
 *
 * Apres commit uniquement : une action annulee (transaction en echec) ne
 * doit pas apparaitre dans les logs comme si elle avait eu lieu.
 */
class AuditObserver implements ShouldHandleEventsAfterCommit
{
    public function __construct(private readonly AuditService $audit) {}

    public function created(Model $model): void
    {
        $this->audit->ecrire(class_basename($model).'.cree', $model, [
            'valeurs' => $this->audit->nettoyer($model->getAttributes()),
        ]);
    }

    public function updated(Model $model): void
    {
        $apres = $this->audit->nettoyer($model->getChanges());

        // Rien de tracable : seul un champ sensible ou technique a change
        // (remember_token a chaque connexion, updated_at).
        if ($apres === []) {
            return;
        }

        // getPrevious() et non getOriginal() : apres commit, l'original est
        // deja resynchronise sur les nouvelles valeurs.
        $avant = $this->audit->nettoyer(array_intersect_key($model->getPrevious(), $apres));

        $this->audit->ecrire(class_basename($model).'.modifie', $model, [
            'avant' => $avant,
            'apres' => $apres,
        ]);
    }

    public function deleted(Model $model): void
    {
        $this->audit->ecrire(class_basename($model).'.supprime', $model, [
            'valeurs' => $this->audit->nettoyer($model->getAttributes()),
        ]);
    }
}
