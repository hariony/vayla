<?php

namespace App\Http\Controllers\Office;

use App\Http\Requests\Office\AttachPhotoRequest;
use App\Http\Requests\Office\OfficeDestinationPhotoRequest;
use App\Http\Requests\Office\OfficeDestinationRequest;
use App\Http\Requests\PhotoOrderRequest;
use App\Models\Destination;
use App\Services\Office\Content\DestinationEditor;
use App\Services\Office\Content\DestinationGalleryEditor;
use App\Services\Office\Content\DestinationQuery;
use App\Services\Photos\TeamPhotoUploader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Les destinations : leur fiche, leur galerie. */
class DestinationController extends OfficeController
{
    public function index(DestinationQuery $lecture): Response
    {
        return Inertia::render('Office/Destinations/Index', $lecture->liste());
    }

    public function create(DestinationQuery $lecture): Response
    {
        return Inertia::render('Office/Destinations/Edit', $lecture->fiche(null));
    }

    public function edit(Destination $destination, DestinationQuery $lecture): Response
    {
        return Inertia::render('Office/Destinations/Edit', $lecture->fiche($destination));
    }

    public function store(OfficeDestinationRequest $request, DestinationEditor $destinations): RedirectResponse
    {
        $destination = $destinations->creer($this->admin($request), $request->toDto());

        return redirect()->route('office.destinations.edit', $destination)->with('succes', "« {$destination->name} » créée.");
    }

    public function update(OfficeDestinationRequest $request, Destination $destination, DestinationEditor $destinations): RedirectResponse
    {
        $destinations->modifier($this->admin($request), $destination, $request->toDto());

        return back()->with('succes', "« {$destination->name} » enregistrée.");
    }

    public function uploadPhoto(OfficeDestinationPhotoRequest $request, Destination $destination, TeamPhotoUploader $televersement): RedirectResponse
    {
        $televersement->televerser($this->admin($request), $request->toDto($destination->id));

        return back()->with('succes', 'Photo ajoutée au bout de la galerie. Glissez-la en tête pour en faire la couverture.');
    }

    public function attachPhoto(AttachPhotoRequest $request, Destination $destination, DestinationGalleryEditor $galerie): RedirectResponse
    {
        $galerie->ajouterDeLaPhototheque($this->admin($request), $destination, $request->photoId());

        return back()->with('succes', 'Photo ajoutée à la galerie.');
    }

    public function reorderPhotos(PhotoOrderRequest $request, Destination $destination, DestinationGalleryEditor $galerie): RedirectResponse
    {
        $galerie->ordonner($this->admin($request), $destination, $request->ids());

        return back()->with('succes', 'Galerie rangée. La première photo est la couverture.');
    }

    public function detachPhoto(Request $request, Destination $destination, int $photo, DestinationGalleryEditor $galerie): RedirectResponse
    {
        $galerie->retirer($this->admin($request), $destination, $photo);

        return back()->with('succes', 'Photo retirée de la galerie.');
    }

    public function destroy(Request $request, Destination $destination, DestinationEditor $destinations): RedirectResponse
    {
        $destinations->supprimer($this->admin($request), $destination);

        return redirect()->route('office.destinations')->with('succes', "« {$destination->name} » supprimée.");
    }
}
