<?php

namespace App\Http\Controllers\Office;

use App\Services\Office\Dashboard\DashboardQuery;
use Inertia\Inertia;
use Inertia\Response;

/** Le tableau de bord : ce qui attend quelqu'un, avant ce qui se mesure. */
class DashboardController extends OfficeController
{
    public function __invoke(DashboardQuery $tableau): Response
    {
        return Inertia::render('Office/Dashboard', $tableau->page());
    }
}
