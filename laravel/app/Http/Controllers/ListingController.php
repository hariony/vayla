<?php

namespace App\Http\Controllers;

use App\Data\SejourData;
use App\Http\Requests\ListingIndexRequest;
use App\Services\CatalogueService;
use Illuminate\Http\Request;
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
     * critère** : il pré-remplit le calendrier de la fiche pour que le
     * voyageur venu du moteur de recherche ne ressaisisse pas ses dates. Il
     * n'est donc pas validé ici — `SejourData::depuis()` rend `null` sur
     * n'importe quoi, et le composant refuse de son côté un séjour qui
     * chevauche une nuit prise. Une suggestion irrecevable ne pré-remplit
     * rien ; elle ne fait jamais échouer la page.
     */
    public function show(Request $request, string $slug): Response
    {
        $sejour = SejourData::depuis($request->query('arrival'), $request->query('departure'));

        return Inertia::render('Listings/Show', $this->service->show($slug, $sejour));
    }
}
