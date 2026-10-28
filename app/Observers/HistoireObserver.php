<?php

namespace App\Observers;

use App\Models\Histoire;
use App\Services\AuditService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

/**
 * Evenement dedie au circuit editorial : retrouver qui a publie, valide ou
 * renvoye une histoire sans parcourir tous les Histoire.modifie. Apres commit,
 * comme AuditObserver.
 */
class HistoireObserver implements ShouldHandleEventsAfterCommit
{
    public function __construct(private readonly AuditService $audit) {}

    public function updated(Histoire $histoire): void
    {
        if (! $histoire->wasChanged('etat')) {
            return;
        }

        $this->audit->ecrire('Histoire.etat_change', $histoire, [
            'de' => $histoire->getPrevious()['etat'] ?? null,
            'vers' => $histoire->etat->value,
        ]);
    }
}
