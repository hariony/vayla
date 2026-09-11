<?php

namespace App\Http\Controllers\Office;

use App\Services\Office\OfficeReadService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TravellerController extends OfficeController
{
    public function __invoke(Request $request, OfficeReadService $lecture): Response
    {
        $q = trim((string) $request->query('q'));

        return Inertia::render('Office/Travellers/Index', $lecture->voyageurs($q === '' ? null : mb_substr($q, 0, 80)));
    }
}
