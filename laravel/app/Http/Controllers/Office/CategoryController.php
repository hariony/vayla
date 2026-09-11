<?php

namespace App\Http\Controllers\Office;

use App\Http\Requests\Office\MoveRequest;
use App\Http\Requests\Office\OfficeCategoryRequest;
use App\Models\Category;
use App\Services\Office\Content\CategoryEditor;
use App\Services\Office\Content\CategoryQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Le rail de catégories de l'accueil. */
class CategoryController extends OfficeController
{
    public function index(CategoryQuery $lecture): Response
    {
        return Inertia::render('Office/Categories/Index', $lecture->page());
    }

    public function store(OfficeCategoryRequest $request, CategoryEditor $categories): RedirectResponse
    {
        $categorie = $categories->creer($this->admin($request), $request->toDto());

        return back()->with('succes', "« {$categorie->label} » ajoutée au bout du rail.");
    }

    public function update(OfficeCategoryRequest $request, Category $categorie, CategoryEditor $categories): RedirectResponse
    {
        $categories->modifier($this->admin($request), $categorie, $request->toDto());

        return back()->with('succes', "« {$categorie->label} » enregistrée.");
    }

    public function move(MoveRequest $request, Category $categorie, CategoryEditor $categories): RedirectResponse
    {
        $categories->deplacer($this->admin($request), $categorie, $request->sens());

        return back();
    }

    public function destroy(Request $request, Category $categorie, CategoryEditor $categories): RedirectResponse
    {
        $categories->supprimer($this->admin($request), $categorie);

        return back()->with('succes', "« {$categorie->label} » supprimée.");
    }
}
