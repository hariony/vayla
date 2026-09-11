<?php

namespace App\Http\Controllers\Office;

use App\Http\Requests\Office\OfficeDestinationPhotoRequest;
use App\Http\Requests\Office\OfficeDestinationRequest;
use App\Models\Destination;
use App\Services\Office\OfficeContentReadService;
use App\Services\Office\OfficeContentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DestinationController extends OfficeController
{
    public function __construct(
        private OfficeContentService $contenu,
        private OfficeContentReadService $lecture,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Office/Destinations/Index', $this->lecture->destinations());
    }

    public function create(): Response
    {
        return Inertia::render('Office/Destinations/Edit', $this->lecture->destination(null));
    }

    public function edit(Destination $destination): Response
    {
        return Inertia::render('Office/Destinations/Edit', $this->lecture->destination($destination));
    }

    public function store(OfficeDestinationRequest $request): RedirectResponse
    {
        $destination = $this->contenu->enregistrerDestination($this->admin($request), null, $request->validated());

        return redirect()->route('office.destinations.edit', $destination)->with('succes', "« {$destination->name} » créée.");
    }

    public function update(OfficeDestinationRequest $request, Destination $destination): RedirectResponse
    {
        $this->contenu->enregistrerDestination($this->admin($request), $destination, $request->validated());

        return back()->with('succes', "« {$destination->name} » enregistrée.");
    }

    public function uploadPhoto(OfficeDestinationPhotoRequest $request, Destination $destination): RedirectResponse
    {
        $this->contenu->ajouterPhotoDestination($this->admin($request), $destination, $request->file('photo'), $request->validated());

        return back()->with('succes', 'Photo ajoutée au bout de la galerie. Glissez-la en tête pour en faire la couverture.');
    }

    public function attachPhoto(Request $request, Destination $destination): RedirectResponse
    {
        $id = (int) $request->validate(['photo_id' => ['required', 'integer']])['photo_id'];

        $this->contenu->ajouterDeLaPhototheque($this->admin($request), $destination, $id);

        return back()->with('succes', 'Photo ajoutée à la galerie.');
    }

    public function reorderPhotos(Request $request, Destination $destination): RedirectResponse
    {
        $ids = $request->validate(['ids' => ['required', 'array', 'max:60'], 'ids.*' => ['integer']])['ids'];

        $this->contenu->ordonnerPhotosDestination($this->admin($request), $destination, $ids);

        return back()->with('succes', 'Galerie rangée. La première photo est la couverture.');
    }

    public function detachPhoto(Request $request, Destination $destination, int $photo): RedirectResponse
    {
        $this->contenu->retirerDeLaGalerie($this->admin($request), $destination, $photo);

        return back()->with('succes', 'Photo retirée de la galerie.');
    }

    public function destroy(Request $request, Destination $destination): RedirectResponse
    {
        $this->contenu->supprimerDestination($this->admin($request), $destination);

        return redirect()->route('office.destinations')->with('succes', "« {$destination->name} » supprimée.");
    }
}
