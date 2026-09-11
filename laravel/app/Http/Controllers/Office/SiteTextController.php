<?php

namespace App\Http\Controllers\Office;

use App\Http\Requests\Office\OfficeSiteTextRequest;
use App\Services\Content\Texts\SiteTextEditor;
use App\Services\Content\Texts\SiteTextQuery;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/** Les textes du site : l'accueil et le pied de page, groupe par groupe. */
class SiteTextController extends OfficeController
{
    public function index(SiteTextQuery $textes): Response
    {
        return Inertia::render('Office/Texts/Index', $textes->page());
    }

    public function update(OfficeSiteTextRequest $request, SiteTextEditor $textes): RedirectResponse
    {
        $changes = $textes->enregistrer($this->admin($request), $request->toDto());

        return back()->with('succes', $changes === []
            ? 'Rien n’a changé.'
            : 'Textes enregistrés, en ligne dès maintenant : '.mb_strtolower(implode(', ', $changes)).'.');
    }
}
