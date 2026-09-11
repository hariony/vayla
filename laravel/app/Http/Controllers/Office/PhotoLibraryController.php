<?php

namespace App\Http\Controllers\Office;

use App\Http\Requests\Office\OfficePhotoCreditRequest;
use App\Http\Requests\Office\OfficePhotoUploadRequest;
use App\Http\Requests\Office\PhotoLibraryIndexRequest;
use App\Models\Photo;
use App\Services\Photos\PhotoCreditEditor;
use App\Services\Photos\PhotoLibraryQuery;
use App\Services\Photos\PhotoRemover;
use App\Services\Photos\TeamPhotoUploader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** La photothèque : les photos du site, leurs crédits, où chacune apparaît. */
class PhotoLibraryController extends OfficeController
{
    public function index(PhotoLibraryIndexRequest $request, PhotoLibraryQuery $phototheque): Response
    {
        return Inertia::render('Office/Photos/Index', $phototheque->page($request->toDto()));
    }

    public function store(OfficePhotoUploadRequest $request, TeamPhotoUploader $televersement): RedirectResponse
    {
        $resultat = $televersement->televerser($this->admin($request), $request->toDto());

        return redirect()->route('office.photos', ['onglet' => 'televersees', 'photo' => $resultat->photo->id])
            ->with('succes', $resultat->destination
                ? "Photo ajoutée à la photothèque et au bout de la galerie de {$resultat->destination->name}."
                : 'Photo ajoutée à la photothèque. Elle ne sera créditée au pied de page qu’une fois dans une galerie.');
    }

    public function update(OfficePhotoCreditRequest $request, Photo $photo, PhotoCreditEditor $credits): RedirectResponse
    {
        $credits->corriger($this->admin($request), $photo, $request->toDto());

        return back()->with('succes', 'Crédit enregistré : il s’affiche aussitôt au pied des pages.');
    }

    public function destroy(Request $request, Photo $photo, PhotoRemover $suppression): RedirectResponse
    {
        $suppression->supprimer($this->admin($request), $photo);

        return back()->with('succes', 'Photo supprimée, fichiers compris.');
    }
}
