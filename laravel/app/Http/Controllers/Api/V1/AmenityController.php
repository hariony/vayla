<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AmenityService;
use Illuminate\Http\JsonResponse;

class AmenityController extends Controller
{
    public function __construct(
        private AmenityService $service,
    ) {}

    /**
     * Le vocabulaire complet. L'application mobile ne doit jamais réécrire
     * ces libellés : elle les lit ici, comme elle lit les niveaux de
     * confiance sur /trust-levels.
     */
    public function index(): JsonResponse
    {
        return response()->json(['data' => $this->service->catalogue()]);
    }

    /** Les équipements sur lesquels la recherche accepte un filtre. */
    public function filters(): JsonResponse
    {
        return response()->json(['data' => $this->service->filters()]);
    }
}
