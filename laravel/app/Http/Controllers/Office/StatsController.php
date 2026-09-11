<?php

namespace App\Http\Controllers\Office;

use App\Http\Requests\Office\StatsRequest;
use App\Services\Office\Stats\CatalogueStatsQuery;
use App\Services\Office\Stats\RequestStatsQuery;
use App\Services\Office\Stats\StayStatsQuery;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Les statistiques, en trois écrans : les demandes, les séjours et la
 * commission, le catalogue et les inscriptions. Tout tenait sur une page qui
 * gonflait à chaque graphique ajouté.
 *
 * La période et l'inclusion de la démonstration vivent dans l'adresse, et
 * suivent d'un écran à l'autre : une vue se partage et survit au retour
 * arrière, comme un filtre du catalogue.
 */
class StatsController extends OfficeController
{
    public function demandes(StatsRequest $request, RequestStatsQuery $stats): Response
    {
        return Inertia::render('Office/Stats/Demandes', $stats->page($request->toDto()));
    }

    public function sejours(StatsRequest $request, StayStatsQuery $stats): Response
    {
        return Inertia::render('Office/Stats/Sejours', $stats->page($request->toDto()));
    }

    public function catalogue(StatsRequest $request, CatalogueStatsQuery $stats): Response
    {
        return Inertia::render('Office/Stats/Catalogue', $stats->page($request->toDto()));
    }
}
