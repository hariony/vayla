<?php

namespace App\Http\Controllers\Office;

use App\Http\Requests\Office\MoveRequest;
use App\Http\Requests\Office\OfficeAmenityRequest;
use App\Models\Amenity;
use App\Services\Office\Content\AmenityEditor;
use App\Services\Office\Content\AmenityQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Le vocabulaire des équipements. */
class AmenityController extends OfficeController
{
    public function index(AmenityQuery $lecture): Response
    {
        return Inertia::render('Office/Amenities/Index', $lecture->page());
    }

    public function store(OfficeAmenityRequest $request, AmenityEditor $equipements): RedirectResponse
    {
        $equipement = $equipements->creer($this->admin($request), $request->toDto());

        return back()->with('succes', "« {$equipement->label} » ajouté. Les propriétaires peuvent le cocher dès maintenant.");
    }

    public function update(OfficeAmenityRequest $request, Amenity $equipement, AmenityEditor $equipements): RedirectResponse
    {
        $equipements->modifier($this->admin($request), $equipement, $request->toDto());

        return back()->with('succes', "« {$equipement->label} » enregistré.");
    }

    public function move(MoveRequest $request, Amenity $equipement, AmenityEditor $equipements): RedirectResponse
    {
        $equipements->deplacer($this->admin($request), $equipement, $request->sens());

        return back();
    }

    public function destroy(Request $request, Amenity $equipement, AmenityEditor $equipements): RedirectResponse
    {
        $equipements->supprimer($this->admin($request), $equipement);

        return back()->with('succes', "« {$equipement->label} » supprimé.");
    }
}
