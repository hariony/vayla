<?php

namespace App\Http\Controllers\Office;

use App\Enums\AdminActionKind;
use App\Services\Office\OfficeReadService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JournalController extends OfficeController
{
    public function __invoke(Request $request, OfficeReadService $lecture): Response
    {
        $familles = collect(AdminActionKind::cases())->map->famille()->unique();
        $famille = $familles->contains($request->query('famille')) ? (string) $request->query('famille') : null;

        return Inertia::render('Office/Journal/Index', $lecture->journal($famille));
    }
}
