<?php

namespace App\Http\Controllers\Office;

use App\Http\Requests\Office\OfficeCategoryRequest;
use App\Models\Category;
use App\Services\Office\OfficeContentReadService;
use App\Services\Office\OfficeContentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends OfficeController
{
    public function __construct(
        private OfficeContentService $contenu,
    ) {}

    public function index(OfficeContentReadService $lecture): Response
    {
        return Inertia::render('Office/Categories/Index', $lecture->categories());
    }

    public function store(OfficeCategoryRequest $request): RedirectResponse
    {
        $categorie = $this->contenu->enregistrerCategorie($this->admin($request), null, $request->validated());

        return back()->with('succes', "« {$categorie->label} » ajoutée au bout du rail.");
    }

    public function update(OfficeCategoryRequest $request, Category $categorie): RedirectResponse
    {
        $this->contenu->enregistrerCategorie($this->admin($request), $categorie, $request->validated());

        return back()->with('succes', "« {$categorie->label} » enregistrée.");
    }

    public function move(Request $request, Category $categorie): RedirectResponse
    {
        $this->contenu->deplacerCategorie($this->admin($request), $categorie, $request->input('sens') === 'haut' ? 'haut' : 'bas');

        return back();
    }

    public function destroy(Request $request, Category $categorie): RedirectResponse
    {
        $this->contenu->supprimerCategorie($this->admin($request), $categorie);

        return back()->with('succes', "« {$categorie->label} » supprimée.");
    }
}
