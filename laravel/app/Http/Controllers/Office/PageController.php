<?php

namespace App\Http\Controllers\Office;

use App\Http\Requests\Office\OfficePageRequest;
use App\Http\Requests\Office\PagePreviewRequest;
use App\Models\Page;
use App\Services\Content\Pages\PageEditor;
use App\Services\Content\Pages\PageQuery;
use App\Services\Content\Pages\PageRenderer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Les pages éditoriales du site, depuis le back-office. */
class PageController extends OfficeController
{
    public function __construct(
        private PageQuery $lecture,
        private PageEditor $pages,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Office/Pages/Index', $this->lecture->liste());
    }

    public function create(): Response
    {
        return Inertia::render('Office/Pages/Edit', $this->lecture->edition(null));
    }

    public function edit(Page $page): Response
    {
        return Inertia::render('Office/Pages/Edit', $this->lecture->edition($page));
    }

    public function store(OfficePageRequest $request): RedirectResponse
    {
        $page = $this->pages->creer($this->admin($request), $request->toDto());

        return redirect()->route('office.pages.edit', $page)->with('succes', "« {$page->title} » créée, en brouillon.");
    }

    public function update(OfficePageRequest $request, Page $page): RedirectResponse
    {
        $this->pages->modifier($this->admin($request), $page, $request->toDto());

        return back()->with('succes', $page->is_published ? 'Enregistrée : la page publiée est à jour.' : 'Brouillon enregistré.');
    }

    public function publish(Request $request, Page $page): RedirectResponse
    {
        $this->pages->publier($this->admin($request), $page);

        return back()->with('succes', "« {$page->title} » est en ligne.");
    }

    public function unpublish(Request $request, Page $page): RedirectResponse
    {
        $this->pages->depublier($this->admin($request), $page);

        return back()->with('succes', "« {$page->title} » n’est plus sur le site. Son lien a quitté le pied de page.");
    }

    public function destroy(Request $request, Page $page): RedirectResponse
    {
        $this->pages->supprimer($this->admin($request), $page);

        return redirect()->route('office.pages')->with('succes', "« {$page->title} » supprimée.");
    }

    /**
     * L'aperçu, rendu **par le serveur** : c'est le même moteur que la page
     * publiée, donc ce qu'on voit est ce qui sortira — un rendu Markdown côté
     * navigateur aurait pu différer du vrai sur un détail.
     *
     * Une réponse JSON explicite : rendu tel quel, un objet Data répondrait
     * « 201 Created » à ce POST, qui ne crée rien.
     */
    public function preview(PagePreviewRequest $request, PageRenderer $rendu): JsonResponse
    {
        return response()->json($rendu->rendre($request->body()));
    }
}
