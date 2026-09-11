<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListingIndexRequest;
use App\Http\Requests\ListingShowRequest;
use App\Services\CatalogueService;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Le catalogue côté site. Même service, mêmes règles et même validation que
 * `/api/v1/listings` : le site et l'application Flutter ne peuvent pas
 * diverger sur ce qu'est une annonce ni sur ce qu'un filtre veut dire.
 */
class ListingController extends Controller
{
    public function __construct(
        private CatalogueService $service,
    ) {}

    public function index(ListingIndexRequest $request): Response
    {
        return Inertia::render('Listings/Index', $this->service->index($request->toFiltre()));
    }

    /**
     * Le séjour éventuellement porté par l'URL est **une suggestion, pas un
     * critère** (`ListingShowRequest`) : le composant refuse de son côté un
     * séjour qui chevauche une nuit prise.
     */
    public function show(ListingShowRequest $request, string $slug): Response
    {
        return Inertia::render('Listings/Show', $this->service->show($slug, $request->sejour()));
    }
}
