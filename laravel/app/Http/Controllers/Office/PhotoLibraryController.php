<?php

namespace App\Http\Controllers\Office;

use App\Http\Requests\Office\OfficePhotoCreditRequest;
use App\Http\Requests\Office\OfficePhotoUploadRequest;
use App\Models\Destination;
use App\Models\Photo;
use App\Services\Office\OfficeContentService;
use App\Services\Office\OfficePhotoLibraryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * La photothèque : toutes les photographies du site, leurs crédits, où
 * chacune apparaît — et le téléversement sans passer par une destination.
 */
class PhotoLibraryController extends OfficeController
{
    public function __construct(
        private OfficePhotoLibraryService $phototheque,
        private OfficeContentService $contenu,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Office/Photos/Index', $this->phototheque->liste(
            (string) $request->query('onglet', 'lieux'),
            trim((string) $request->query('q')),
            $request->integer('photo') ?: null,
        ));
    }

    public function store(OfficePhotoUploadRequest $request): RedirectResponse
    {
        $credit = $request->validated();
        $destination = isset($credit['destination_id']) ? Destination::query()->find($credit['destination_id']) : null;

        // Téléversée pour une destination, elle passe par la galerie : même
        // chemin, même journal que depuis la page de la destination.
        $photo = $destination
            ? $this->contenu->ajouterPhotoDestination($this->admin($request), $destination, $request->file('photo'), $credit)
            : $this->phototheque->televerser($this->admin($request), $request->file('photo'), $credit);

        return redirect()->route('office.photos', ['onglet' => 'televersees', 'photo' => $photo->id])
            ->with('succes', $destination
                ? "Photo ajoutée à la photothèque et au bout de la galerie de {$destination->name}."
                : 'Photo ajoutée à la photothèque. Elle ne sera créditée au pied de page qu’une fois dans une galerie.');
    }

    public function update(OfficePhotoCreditRequest $request, Photo $photo): RedirectResponse
    {
        $this->phototheque->modifier($this->admin($request), $photo, $request->validated());

        return back()->with('succes', 'Crédit enregistré : il s’affiche aussitôt au pied des pages.');
    }

    public function destroy(Request $request, Photo $photo): RedirectResponse
    {
        $this->phototheque->supprimer($this->admin($request), $photo);

        return back()->with('succes', 'Photo supprimée, fichiers compris.');
    }
}
