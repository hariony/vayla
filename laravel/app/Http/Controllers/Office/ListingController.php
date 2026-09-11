<?php

namespace App\Http\Controllers\Office;

use App\Enums\ListingStatus;
use App\Enums\TrustLevel;
use App\Http\Requests\Office\OfficeReasonRequest;
use App\Http\Requests\Office\OfficeTrustRequest;
use App\Models\Listing;
use App\Services\Office\ModerationService;
use App\Services\Office\OfficeReadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * La modération des annonces. Un refus des règles (`OfficeRefusal`) remonte en
 * bandeau d'erreur, par le gestionnaire déclaré dans `bootstrap/app.php`.
 */
class ListingController extends OfficeController
{
    public function __construct(
        private OfficeReadService $lecture,
        private ModerationService $moderation,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Office/Listings/Index', $this->lecture->annonces(
            ListingStatus::tryFrom((string) $request->query('statut')),
            $this->recherche($request),
        ));
    }

    public function show(Listing $listing): Response
    {
        return Inertia::render('Office/Listings/Show', $this->lecture->annonce($listing));
    }

    public function publish(Request $request, Listing $listing): RedirectResponse
    {
        $this->moderation->publier($this->admin($request), $listing);

        return back()->with('succes', "« {$listing->title} » est en ligne.");
    }

    public function sendBack(OfficeReasonRequest $request, Listing $listing): RedirectResponse
    {
        $this->moderation->renvoyer($this->admin($request), $listing, $request->validated('reason'));

        return back()->with('succes', 'Fiche renvoyée : le propriétaire lit votre motif dans son espace.');
    }

    public function archive(OfficeReasonRequest $request, Listing $listing): RedirectResponse
    {
        $this->moderation->archiver($this->admin($request), $listing, $request->validated('reason'));

        return back()->with('succes', "« {$listing->title} » est archivée.");
    }

    public function trust(OfficeTrustRequest $request, Listing $listing): RedirectResponse
    {
        $niveau = TrustLevel::from((int) $request->validated('level'));

        $this->moderation->niveau($this->admin($request), $listing, $niveau, $request->validated('note'));

        return back()->with('succes', "Niveau {$niveau->value} — {$niveau->label()}.");
    }

    private function recherche(Request $request): ?string
    {
        $q = trim((string) $request->query('q'));

        return $q === '' ? null : mb_substr($q, 0, 80);
    }
}
