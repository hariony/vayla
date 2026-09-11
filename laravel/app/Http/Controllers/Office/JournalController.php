<?php

namespace App\Http\Controllers\Office;

use App\Http\Requests\Office\JournalRequest;
use App\Services\Office\Journal\JournalQuery;
use Inertia\Inertia;
use Inertia\Response;

/** Le journal de l'équipe. */
class JournalController extends OfficeController
{
    public function __invoke(JournalRequest $request, JournalQuery $journal): Response
    {
        return Inertia::render('Office/Journal/Index', $journal->page($request->famille()));
    }
}
