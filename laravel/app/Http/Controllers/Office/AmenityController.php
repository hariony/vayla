<?php

namespace App\Http\Controllers\Office;

use App\Http\Requests\Office\OfficeAmenityRequest;
use App\Models\Amenity;
use App\Services\Office\OfficeContentReadService;
use App\Services\Office\OfficeContentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AmenityController extends OfficeController
{
    public function __construct(
        private OfficeContentService $contenu,
    ) {}

    public function index(OfficeContentReadService $lecture): Response
    {
        return Inertia::render('Office/Amenities/Index', $lecture->equipements());
    }

    public function store(OfficeAmenityRequest $request): RedirectResponse
    {
        $equipement = $this->contenu->enregistrerEquipement($this->admin($request), null, $request->validated());

        return back()->with('succes', "« {$equipement->label} » ajouté. Les propriétaires peuvent le cocher dès maintenant.");
    }

    public function update(OfficeAmenityRequest $request, Amenity $equipement): RedirectResponse
    {
        $this->contenu->enregistrerEquipement($this->admin($request), $equipement, $request->validated());

        return back()->with('succes', "« {$equipement->label} » enregistré.");
    }

    public function move(Request $request, Amenity $equipement): RedirectResponse
    {
        $this->contenu->deplacerEquipement($this->admin($request), $equipement, $request->input('sens') === 'haut' ? 'haut' : 'bas');

        return back();
    }

    public function destroy(Request $request, Amenity $equipement): RedirectResponse
    {
        $this->contenu->supprimerEquipement($this->admin($request), $equipement);

        return back()->with('succes', "« {$equipement->label} » supprimé.");
    }
}
