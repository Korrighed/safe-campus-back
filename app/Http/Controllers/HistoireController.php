<?php

namespace App\Http\Controllers;

use App\Enums\EtatHistoire;
use App\Http\Resources\HistoireResource;
use App\Models\Histoire;
use App\Services\HistoireParcoursService;

class HistoireController extends Controller
{
    public function show(Histoire $histoire, HistoireParcoursService $parcours): HistoireResource
    {
        abort_unless($histoire->etat === EtatHistoire::Publie, 404);

        return new HistoireResource($parcours->charger($histoire));
    }
}
