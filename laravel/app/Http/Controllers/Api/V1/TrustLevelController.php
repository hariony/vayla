<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\TrustLadderService;
use Illuminate\Http\JsonResponse;

/**
 * L'échelle de confiance est publiée : l'application mobile doit afficher
 * exactement les mêmes libellés que le site, sans les recopier.
 */
class TrustLevelController extends Controller
{
    public function __construct(
        private TrustLadderService $service,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json(['data' => $this->service->ladder()]);
    }
}
