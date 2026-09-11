<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\DestinationService;
use Illuminate\Http\JsonResponse;

class DestinationController extends Controller
{
    public function __construct(
        private DestinationService $service,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => $this->service->atlas(),
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        return response()->json([
            'data' => $this->service->show($slug),
        ]);
    }
}
