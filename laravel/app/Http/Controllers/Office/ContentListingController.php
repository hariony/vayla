<?php

namespace App\Http\Controllers\Office;

use App\Http\Requests\Office\OfficeListingRequest;
use App\Http\Requests\Office\PhotoOrderRequest;
use App\Http\Requests\OwnerPhotoRequest;
use App\Models\Listing;
use App\Models\Owner;
use App\Services\Office\Content\ListingContentEditor;
use App\Services\Office\Content\ListingContentQuery;
use App\Services\Office\Content\ListingPhotoEditor;
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
    public function edit(Listing $listing, ListingContentQuery $lecture): Response
    {
        return Inertia::render('Office/Listings/Edit', $lecture->page($listing));
    }

    public function update(OfficeListingRequest $request, Listing $listing, ListingContentEditor $contenu): RedirectResponse
    {
        $changes = $contenu->modifier($this->admin($request), $listing, $request->toDto());

        return back()->with('succes', $changes === [] ? 'Rien n’a changé.' : 'Annonce enregistrée : '.implode(', ', $changes).'.');
    }

    public function create(Owner $owner, ListingContentQuery $lecture): Response
    {
        return Inertia::render('Office/Listings/Edit', $lecture->page(null, $owner));
    }

    public function store(OfficeListingRequest $request, Owner $owner, ListingContentEditor $contenu): RedirectResponse
    {
        $listing = $contenu->creer($this->admin($request), $owner, $request->toDto());

        return redirect()->route('office.listings.edit', ['listing' => $listing->id, 'section' => 'photos'])
            ->with('succes', "Annonce créée en brouillon pour {$owner->name}. Ajoutez ses photos.");
    }

    public function uploadPhoto(OwnerPhotoRequest $request, Listing $listing, ListingPhotoEditor $photos): RedirectResponse
    {
        $photos->ajouter($this->admin($request), $listing, $request->file('photo'), $request->string('caption')->value() ?: null);

        return back()->with('succes', 'Photo ajoutée.');
    }

    public function deletePhoto(Request $request, Listing $listing, int $photo, ListingPhotoEditor $photos): RedirectResponse
    {
        $photos->retirer($this->admin($request), $listing, $photo);

        return back()->with('succes', 'Photo retirée.');
    }

    public function reorderPhotos(PhotoOrderRequest $request, Listing $listing, ListingPhotoEditor $photos): RedirectResponse
    {
        $photos->ordonner($this->admin($request), $listing, $request->ids());

        return back()->with('succes', 'Ordre enregistré. La première photo est la couverture.');
    }
}
