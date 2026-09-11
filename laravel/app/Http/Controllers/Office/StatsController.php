<?php

namespace App\Http\Controllers\Office;

use App\Http\Requests\Office\StatsRequest;
use App\Services\Office\Stats\StatsQuery;
use Inertia\Inertia;
use Inertia\Response;

/**
 * La période et l'inclusion de la démonstration vivent dans l'adresse : une
 * vue se partage et survit au retour arrière, comme un filtre du catalogue.
 */
class StatsController extends OfficeController
{
    public function __invoke(StatsRequest $request, StatsQuery $stats): Response
    {
        return Inertia::render('Office/Stats/Index', $stats->page($request->toDto()));
    }
}
