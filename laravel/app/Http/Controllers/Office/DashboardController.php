<?php

namespace App\Http\Controllers\Office;

use App\Services\Office\OfficeReadService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends OfficeController
{
    public function __invoke(OfficeReadService $lecture): Response
    {
        return Inertia::render('Office/Dashboard', $lecture->tableau());
    }
}
