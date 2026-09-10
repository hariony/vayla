<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\OwnerListingRequest;
use App\Http\Requests\OwnerPhotoRequest;
use App\Models\Listing;
use App\Models\Photo;
use App\Services\OwnerListingService;
use App\Services\PhotoUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

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
        private OwnerListingService $annonces,
        private PhotoUploadService $photos,
    ) {}

    public function index(Request $request): Response
    {
        $owner = $request->user('proprietaire');

        return Inertia::render('Owner/Listings/Index', [
            'listings' => $owner->listings->map(fn (Listing $l) => [
                'slug' => $l->slug,
                'title' => $l->title,
                'place' => $l->destination?->name,
                'status' => $l->status->value,
                'statusLabel' => $l->status->label(),
                'consigne' => $l->status->consigne(),
                'price' => $l->price,
                'guests' => $l->guests,
                'bedrooms' => $l->bedrooms,
                'trust' => $l->trust_level->value,
                'trustName' => $l->trust_level->label(),
                'photos' => $l->photos->count(),
                'photo' => $l->photos->first()
                    ? ['key' => $l->photos->first()->key, 'folder' => $l->photos->first()->folder, 'width' => $l->photos->first()->width]
                    : null,
            ])->values()->all(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Owner/Listings/Form', [
            'listing' => null,
        ] + $this->annonces->vocabulaire());
    }

    public function store(OwnerListingRequest $request): RedirectResponse
    {
        $listing = $this->annonces->creer($request->user('proprietaire'), $request->fiche());
        $this->annonces->poserEquipements($listing, $request->validated('amenities') ?? []);

        return redirect()
            ->route('owner.listings.edit', $listing->slug)
            ->with('succes', 'Logement créé. Ajoutez vos photos, puis envoyez la fiche à Vayla.');
    }

    public function edit(Request $request, string $slug): Response
    {
        return Inertia::render('Owner/Listings/Form', [
            'listing' => $this->annonces->pourEdition($this->sien($request, $slug)),
        ] + $this->annonces->vocabulaire());
    }

    public function update(OwnerListingRequest $request, string $slug): RedirectResponse
    {
        $listing = $this->sien($request, $slug);

        $this->annonces->modifier($listing, $request->fiche());

        // Les équipements font partie de ce que Vayla est allé vérifier : on
        // ne les touche plus une fois l'annonce contrôlée.
        if ($listing->status->estModifiable()) {
            $this->annonces->poserEquipements($listing, $request->validated('amenities') ?? []);
        }

        return back()->with('succes', 'Modifications enregistrées.');
    }

    /**
     * Envoie la fiche à la vérification.
     *
     * Un refus dit **ce qui manque**, en toutes lettres : « formulaire
     * incomplet » obligerait le propriétaire à chercher, et il appellerait.
     */
    public function submit(Request $request, string $slug): RedirectResponse
    {
        $manques = $this->annonces->soumettre($this->sien($request, $slug));

        if ($manques !== []) {
            return back()->with('erreur', 'Il manque encore : '.implode(', ', $manques).'.');
        }

        return back()->with('succes',
            'Fiche envoyée à Vayla. Nous vous appelons sous 48 h pour la vérification.');
    }

    public function uploadPhoto(OwnerPhotoRequest $request, string $slug): RedirectResponse
    {
        $listing = $this->sien($request, $slug);

        try {
            $this->photos->ajouter($listing, $request->file('photo'), $request->string('caption')->value() ?: null);
        } catch (RuntimeException $e) {
            return back()->with('erreur', $e->getMessage());
        }

        return back()->with('succes', 'Photo ajoutée.');
    }

    public function deletePhoto(Request $request, string $slug, int $photo): RedirectResponse
    {
        $listing = $this->sien($request, $slug);

        // Une photo qui n'est pas dans cette galerie ne se supprime pas
        // depuis cette galerie : l'identifiant vient du navigateur.
        $cible = $listing->photos->firstWhere('id', $photo) ?? abort(404);

        $this->photos->retirer($listing, Photo::findOrFail($cible->id));

        return back()->with('succes', 'Photo retirée.');
    }

    public function reorderPhotos(Request $request, string $slug): RedirectResponse
    {
        $listing = $this->sien($request, $slug);

        $this->photos->reordonner($listing, $request->input('ids', []));

        return back()->with('succes', 'Ordre des photos enregistré. La première est la photo de couverture.');
    }

    /** La session donne le propriétaire, le propriétaire donne ses annonces — et pas celles des autres. */
    private function sien(Request $request, string $slug): Listing
    {
        return $request->user('proprietaire')->listings->firstWhere('slug', $slug) ?? abort(404);
    }
}
