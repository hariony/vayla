<?php

namespace App\Http\Controllers\Office;

use App\Http\Requests\Office\OfficeSiteTextRequest;
use App\Services\Content\SiteTextService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/** Les textes du site : l'accueil et le pied de page, groupe par groupe. */
class SiteTextController extends OfficeController
{
    public function __construct(
        private SiteTextService $textes,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Office/Texts/Index', ['groupes' => $this->textes->groupes()]);
    }

    public function update(OfficeSiteTextRequest $request): RedirectResponse
    {
        $changes = $this->textes->enregistrer($this->admin($request), $request->validated('groupe'), $request->validated('textes'));

        return back()->with('succes', $changes === []
            ? 'Rien n’a changé.'
            : 'Textes enregistrés, en ligne dès maintenant : '.mb_strtolower(implode(', ', $changes)).'.');
    }
}
