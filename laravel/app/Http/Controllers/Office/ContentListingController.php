<?php

namespace App\Http\Controllers\Office;

use App\Http\Requests\Office\OfficeListingRequest;
use App\Http\Requests\OwnerPhotoRequest;
use App\Models\Listing;
use App\Models\Owner;
use App\Services\Office\OfficeContentReadService;
use App\Services\Office\OfficeContentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Le contenu d'une annonce : texte, capacité, tarif, équipements, catégories,
 * photos. Et la saisie d'une annonce pour un propriétaire qui la dicte.
 */
class ContentListingController extends OfficeController
{
    public function __construct(
        private OfficeContentService $contenu,
        private OfficeContentReadService $lecture,
    ) {}

    public function edit(Listing $listing): Response
    {
        return Inertia::render('Office/Listings/Edit', $this->lecture->editionAnnonce($listing));
    }

    public function update(OfficeListingRequest $request, Listing $listing): RedirectResponse
    {
        $changes = $this->contenu->modifierAnnonce(
            $this->admin($request),
            $listing,
            $request->fiche(),
            $request->validated('amenities', []),
            $request->validated('categories', []),
        );

        return back()->with('succes', $changes === []
            ? 'Rien n’a changé.'
            : 'Annonce enregistrée : '.implode(', ', $changes).'.');
    }

    public function create(Owner $owner): Response
    {
        return Inertia::render('Office/Listings/Edit', $this->lecture->editionAnnonce(null, $owner));
    }

    public function store(OfficeListingRequest $request, Owner $owner): RedirectResponse
    {
        $listing = $this->contenu->creerAnnonce(
            $this->admin($request),
            $owner,
            $request->fiche(),
            $request->validated('amenities', []),
            $request->validated('categories', []),
        );

        return redirect()->route('office.listings.edit', ['listing' => $listing->id, 'section' => 'photos'])
            ->with('succes', "Annonce créée en brouillon pour {$owner->name}. Ajoutez ses photos.");
    }

    public function uploadPhoto(OwnerPhotoRequest $request, Listing $listing): RedirectResponse
    {
        $this->contenu->ajouterPhoto($this->admin($request), $listing, $request->file('photo'), $request->string('caption')->value() ?: null);

        return back()->with('succes', 'Photo ajoutée.');
    }

    public function deletePhoto(Request $request, Listing $listing, int $photo): RedirectResponse
    {
        $this->contenu->retirerPhoto($this->admin($request), $listing, $photo);

        return back()->with('succes', 'Photo retirée.');
    }

    public function reorderPhotos(Request $request, Listing $listing): RedirectResponse
    {
        $ids = $request->validate(['ids' => ['required', 'array', 'max:60'], 'ids.*' => ['integer']])['ids'];

        $this->contenu->ordonnerPhotos($this->admin($request), $listing, $ids);

        return back()->with('succes', 'Ordre enregistré. La première photo est la couverture.');
    }
}
