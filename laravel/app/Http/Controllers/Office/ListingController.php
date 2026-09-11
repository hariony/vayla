<?php

namespace App\Http\Controllers\Office;

use App\Http\Requests\Office\ListingQueueRequest;
use App\Http\Requests\Office\OfficeReasonRequest;
use App\Http\Requests\Office\OfficeTrustRequest;
use App\Models\Listing;
use App\Services\Office\Listings\ListingModerationQuery;
use App\Services\Office\Listings\ListingQueueQuery;
use App\Services\Office\ModerationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** La file des annonces et leur modération. */
class ListingController extends OfficeController
{
    public function __construct(private ModerationService $moderation) {}

    public function index(ListingQueueRequest $request, ListingQueueQuery $file): Response
    {
        return Inertia::render('Office/Listings/Index', $file->page($request->toDto()));
    }

    public function show(Listing $listing, ListingModerationQuery $fiche): Response
    {
        return Inertia::render('Office/Listings/Show', $fiche->page($listing));
    }

    public function publish(Request $request, Listing $listing): RedirectResponse
    {
        $this->moderation->publier($this->admin($request), $listing);

        return back()->with('succes', "« {$listing->title} » est en ligne.");
    }

    public function sendBack(OfficeReasonRequest $request, Listing $listing): RedirectResponse
    {
        $this->moderation->renvoyer($this->admin($request), $listing, (string) $request->reason());

        return back()->with('succes', 'Fiche renvoyée : le propriétaire lit votre motif dans son espace.');
    }

    public function archive(OfficeReasonRequest $request, Listing $listing): RedirectResponse
    {
        $this->moderation->archiver($this->admin($request), $listing, $request->reason());

        return back()->with('succes', "« {$listing->title} » est archivée.");
    }

    public function trust(OfficeTrustRequest $request, Listing $listing): RedirectResponse
    {
        $niveau = $request->niveau();
        $this->moderation->niveau($this->admin($request), $listing, $niveau, $request->note());

        return back()->with('succes', "Niveau {$niveau->value} — {$niveau->label()}.");
    }
}
