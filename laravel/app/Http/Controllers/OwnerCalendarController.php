<?php

namespace App\Http\Controllers;

use App\Data\SejourData;
use App\Enums\BlockReason;
use App\Exceptions\CalendarRefusedException;
use App\Http\Requests\OwnerBlockRequest;
use App\Models\Listing;
use App\Services\OwnerCalendarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Le calendrier d'un logement, côté propriétaire.
 *
 * **Une page à part, atteinte par un bouton explicite.** L'espace
 * propriétaire est un écran unique ordonné par urgence, et y empiler un
 * calendrier par logement l'aurait noyé : ce qui compte à l'ouverture, c'est
 * la demande qui expire, pas les douze mois à venir. Un bouton par logement
 * est une navigation qui ne s'apprend pas — contrairement à des onglets.
 *
 * **Chaque action revérifie que le logement appartient au propriétaire
 * connecté.** Être connecté dit qui l'on est, pas ce qu'on a le droit de
 * toucher : sans ce contrôle, un slug d'annonce recopié depuis le catalogue
 * public suffirait à fermer les dates d'un confrère.
 */
class OwnerCalendarController extends Controller
{
    public function __construct(
        private OwnerCalendarService $calendrier,
    ) {}

    public function show(Request $request, string $slug): Response
    {
        return Inertia::render('Owner/Calendar', $this->calendrier->payload($this->logement($request, $slug)));
    }

    public function store(OwnerBlockRequest $request, string $slug): RedirectResponse
    {
        $listing = $this->logement($request, $slug);

        // Les dates sont déjà validées ; `SejourData` n'est pas ici un
        // garde-fou de plus, c'est le seul endroit du dépôt qui sait tirer
        // la dernière nuit d'une date de départ.
        $sejour = SejourData::depuis($request->input('arrival'), $request->input('departure'));

        if (! $sejour) {
            return back()->with('erreur', 'Ces dates ne forment pas une période.');
        }

        try {
            $this->calendrier->bloquer($listing, $sejour, BlockReason::from($request->input('reason')));
        } catch (CalendarRefusedException $e) {
            return back()->with('erreur', $e->getMessage());
        }

        return back()->with('succes', $sejour->nights.' '.($sejour->nights > 1 ? 'nuits fermées' : 'nuit fermée')
            .' au calendrier. Elles n’apparaissent plus comme libres.');
    }

    public function destroy(Request $request, string $slug, int $id): RedirectResponse
    {
        try {
            $this->calendrier->liberer($this->logement($request, $slug), $id);
        } catch (CalendarRefusedException $e) {
            return back()->with('erreur', $e->getMessage());
        }

        return back()->with('succes', 'Période rouverte. Ces nuits sont de nouveau réservables.');
    }

    /** La session donne le propriétaire, le propriétaire donne ses logements — et pas ceux des autres. */
    private function logement(Request $request, string $slug): Listing
    {
        return $request->user('proprietaire')->listings->firstWhere('slug', $slug) ?? abort(404);
    }
}
