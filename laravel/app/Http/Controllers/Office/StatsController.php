<?php

namespace App\Http\Controllers\Office;

use App\Services\Office\OfficeStatsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StatsController extends OfficeController
{
    /**
     * La période et l'inclusion de la démonstration vivent dans l'adresse :
     * une vue se partage et survit au retour arrière, comme un filtre du
     * catalogue. **La démonstration est incluse par défaut tant qu'elle
     * existe** — sinon l'écran serait vide aujourd'hui — et l'écran le dit.
     */
    public function __invoke(Request $request, OfficeStatsService $stats): Response
    {
        $mois = (int) $request->query('periode', 12);
        $mois = in_array($mois, OfficeStatsService::PERIODES, true) ? $mois : 12;

        return Inertia::render('Office/Stats/Index', $stats->rapport($mois, $request->query('demo') !== '0'));
    }
}
