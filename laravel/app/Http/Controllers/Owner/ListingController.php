<?php

namespace App\Http\Controllers\Owner;

use App\Exceptions\PhotoRefusedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\OwnerListingRequest;
use App\Http\Requests\OwnerPhotoRequest;
use App\Http\Requests\PhotoOrderRequest;
use App\Services\Owners\OwnerListingEditor;
use App\Services\Owners\OwnerListingsQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Les annonces, saisies par leur propriétaire.
 *
 * **Il remplit, Vayla publie.** Une annonce naît en brouillon, se complète, se
 * soumet — et n'apparaît sur le site qu'après le contrôle de Vayla. Le niveau
 * de confiance n'entre par aucune de ces routes : il est attribué, jamais
 * déclaré.
 *
 * **Chaque route revérifie que l'annonce appartient au propriétaire
 * connecté.** Être connecté dit qui l'on est, pas ce qu'on a le droit de
 * toucher : un slug recopié depuis le catalogue public ne doit pas ouvrir la
 * fiche d'un confrère.
 */
class ListingController extends Controller
{
    public function __construct(
        private OwnerListingsQuery $lecture,
        private OwnerListingEditor $annonces,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Owner/Listings/Index', $this->lecture->liste($request->user('proprietaire')));
    }

    public function create(): Response
    {
        return Inertia::render('Owner/Listings/Form', $this->lecture->creation());
    }

    public function store(OwnerListingRequest $request): RedirectResponse
    {
        $listing = $this->annonces->creer($request->user('proprietaire'), $request->toDraftDto());

        // Déposé **sur l'étape des photos** : c'est la prochaine chose à
        // faire, et la seule que la création ne pouvait pas faire. Le
        // formulaire lit `?etape=` à l'ouverture.
        return redirect()
            ->route('owner.listings.edit', ['slug' => $listing->slug, 'etape' => 'photos'])
            ->with('succes', 'Logement créé. Ajoutez vos photos, puis envoyez la fiche à Vayla.');
    }

    public function edit(Request $request, string $slug): Response
    {
        return Inertia::render('Owner/Listings/Form', $this->lecture->edition($request->user('proprietaire'), $slug));
    }

    public function update(OwnerListingRequest $request, string $slug): RedirectResponse
    {
        $this->annonces->modifier($request->user('proprietaire'), $slug, $request->toDraftDto());

        return back()->with('succes', 'Modifications enregistrées.');
    }

    public function submit(Request $request, string $slug): RedirectResponse
    {
        $manques = $this->annonces->soumettre($request->user('proprietaire'), $slug);

        if ($manques !== []) {
            return back()->with('erreur', 'Il manque encore : '.implode(', ', $manques).'.');
        }

        return back()->with('succes', 'Fiche envoyée à Vayla. Nous vous appelons sous 48 h pour la vérification.');
    }

    public function uploadPhoto(OwnerPhotoRequest $request, string $slug): RedirectResponse
    {
        try {
            $this->annonces->ajouterPhoto($request->user('proprietaire'), $slug, $request->photo(), $request->legende());
        } catch (PhotoRefusedException $e) {
            return back()->with('erreur', $e->getMessage());
        }

        return back()->with('succes', 'Photo ajoutée.');
    }

    public function deletePhoto(Request $request, string $slug, int $photo): RedirectResponse
    {
        $this->annonces->retirerPhoto($request->user('proprietaire'), $slug, $photo);

        return back()->with('succes', 'Photo retirée.');
    }

    public function reorderPhotos(PhotoOrderRequest $request, string $slug): RedirectResponse
    {
        $this->annonces->ordonnerPhotos($request->user('proprietaire'), $slug, $request->ids());

        return back()->with('succes', 'Ordre des photos enregistré. La première est la photo de couverture.');
    }
}
