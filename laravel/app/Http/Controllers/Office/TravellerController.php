<?php

namespace App\Http\Controllers\Office;

use App\Http\Requests\Office\OfficeSearchRequest;
use App\Services\Office\Travellers\TravellerQuery;
use Inertia\Inertia;
use Inertia\Response;

/** Les comptes voyageurs. */
class TravellerController extends OfficeController
{
    public function __invoke(OfficeSearchRequest $request, TravellerQuery $voyageurs): Response
    {
        return Inertia::render('Office/Travellers/Index', $voyageurs->page($request->recherche()));
    }
}
