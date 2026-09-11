<?php

namespace App\Http\Controllers;

use App\Services\HomeService;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Reçoit, délègue, retourne. Aucune donnée métier ici : elles vivent en
 * base, le HomeService les assemble.
 */
class HomeController extends Controller
{
    public function __construct(
        private HomeService $service,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Home/Index', $this->service->page());
    }
}
