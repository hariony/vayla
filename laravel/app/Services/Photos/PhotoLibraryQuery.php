<?php

namespace App\Services\Photos;

use App\Contracts\Photos\PhotoStorage;
use App\Contracts\Repositories\DestinationRepositoryInterface;
use App\Contracts\Repositories\PhotoLibraryRepositoryInterface;
use App\Data\Office\ListFilterData;
use App\Data\Office\PaginationData;
use App\Data\Office\TabData;
use App\Data\OptionData;
use App\Data\Photos\DiskUsageData;
use App\Data\Photos\PhotoLibraryItemData;
use App\Data\Photos\PhotoLibraryPageData;
use App\Data\Photos\PhotoPermissionsData;
use App\Data\Photos\PhotoUsageData;
use App\DTOs\Photos\PhotoLibraryFilterDto;
use App\Enums\PhotoLibraryTab;
use App\Enums\PhotoLicence;
use App\Enums\PhotoProvenance;
use App\Models\Destination;
use App\Models\Photo;

/**
 * Lire la photothèque : une page de photos, chacune avec son crédit, ses
 * usages, son poids et ce qu'on peut en faire. Ne modifie rien.
 */
final class PhotoLibraryQuery
{
    private const PAR_PAGE = 48;

    public function __construct(
        private PhotoLibraryRepositoryInterface $photos,
        private DestinationRepositoryInterface $destinations,
        private PhotoStorage $stockage,
        private PhotoRemovalPolicy $suppression,
    ) {}

    public function page(PhotoLibraryFilterDto $filtre): PhotoLibraryPageData
    {
        $page = $this->photos->paginer($filtre->onglet, $filtre->recherche, self::PAR_PAGE);
        $items = collect($page->items());
        $usages = $this->photos->usages($items->pluck('id')->all());

        return new PhotoLibraryPageData(
            photos: $items->map(fn (Photo $p) => $this->fiche($p, $usages[$p->id] ?? []))->values()->all(),
            ouverte: $this->ouverte($filtre->photoOuverte),
            meta: PaginationData::fromPaginator($page),
            onglets: array_map(fn (PhotoLibraryTab $t) => new TabData($t->value, $t->label(), $this->photos->compter($t)), PhotoLibraryTab::cases()),
            filtre: new ListFilterData($filtre->onglet->value, $filtre->recherche),
            disque: $this->disque(),
            licences: array_map(fn (PhotoLicence $l) => new OptionData($l->value, $l->label()), PhotoLicence::cases()),
            destinations: $this->destinations->all()->sortBy('name')
                ->map(fn (Destination $d) => new OptionData($d->id, $d->name))->values()->all(),
        );
    }

    /** Une photo demandée par l'adresse s'ouvre même si elle n'est pas sur la page affichée. */
    private function ouverte(?int $id): ?PhotoLibraryItemData
    {
        $photo = $id ? $this->photos->trouver($id) : null;

        return $photo ? $this->fiche($photo, $this->photos->usages([$photo->id])[$photo->id] ?? []) : null;
    }

    /** @param  array<int, PhotoUsageData>  $usages */
    private function fiche(Photo $p, array $usages): PhotoLibraryItemData
    {
        $provenance = PhotoProvenance::de($p);
        $fichiers = $this->stockage->fichiers($p);
        $raison = $this->suppression->raison($p, $usages);

        return new PhotoLibraryItemData(
            id: $p->id, key: $p->key, folder: $p->folder, width: $p->width,
            caption: $p->caption, author: $p->author,
            licence: $p->licence, licenceUrl: $p->licence_url,
            licenceCle: PhotoLicence::depuisLibelle($p->licence)?->value,
            sourceUrl: $p->source_url,
            provenance: $provenance->value, provenanceLabel: $provenance->label(),
            usages: $usages, poids: $fichiers->poids, paliers: $fichiers->paliers,
            ajoutee: $p->created_at?->toIso8601String(),
            peut: new PhotoPermissionsData(
                legende: $provenance->legendeModifiable(),
                credit: $provenance->creditModifiable(),
                licence: $provenance->licenceModifiable(),
                supprimer: $raison === null,
            ),
            pourquoiPasSupprimer: $raison,
        );
    }

    private function disque(): DiskUsageData
    {
        $lieux = $this->stockage->poidsDossier('lieux');
        $destinations = $this->stockage->poidsDossier('destinations');
        $annonces = $this->stockage->poidsDossier('annonces');

        return new DiskUsageData($lieux + $destinations + $annonces, $lieux, $destinations, $annonces);
    }
}
