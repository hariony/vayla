<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function __construct(
        private CategoryService $service,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json(['data' => $this->service->rail()]);
    }
}
