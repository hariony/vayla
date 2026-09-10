<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListingIndexRequest;
use App\Services\ListingService;
use Illuminate\Http\JsonResponse;

/**
 * Même service que la page d'accueil, autre sortie. C'est tout l'intérêt du
 * découpage : l'application Flutter et le site ne peuvent pas diverger sur
 * ce qu'est une annonce ou sur son niveau de confiance.
 */
class ListingController extends Controller
{
    public function __construct(
        private ListingService $service,
    ) {}

    public function index(ListingIndexRequest $request): JsonResponse
    {
        $page = $this->service->search(
            $request->toFiltre(),
            (bool) config('vayla.demo')
        );

        return response()->json([
            'data' => $page->items(),
            'meta' => [
                'page' => $page->currentPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'pages' => $page->lastPage(),
                'demo' => (bool) config('vayla.demo'),
            ],
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        return response()->json([
            'data' => $this->service->show($slug, (bool) config('vayla.demo')),
        ]);
    }
}
