<?php

namespace App\Http\Controllers;

use App\Services\DestinationPageService;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Les destinations côté site. Même service et mêmes compteurs calculés que
 * l'atlas de l'accueil et que `/api/v1/destinations`.
 */
class DestinationController extends Controller
{
    public function __construct(
        private DestinationPageService $service,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Destinations/Index', $this->service->index());
    }

    public function show(string $slug): Response
    {
        return Inertia::render('Destinations/Show', $this->service->show($slug));
    }
}
