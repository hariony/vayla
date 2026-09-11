<?php

namespace App\Services\Office\Content;

use App\Contracts\Repositories\DestinationGalleryRepositoryInterface;
use App\Contracts\Repositories\OfficeDestinationRepositoryInterface;
use App\Data\Office\Content\DestinationEditPageData;
use App\Data\Office\Content\DestinationFormData;
use App\Data\Office\Content\DestinationRowData;
use App\Data\Office\Content\DestinationsPageData;
use App\Data\Office\Content\GalleryPhotoData;
use App\Data\OptionData;
use App\Enums\ClimateZone;
use App\Enums\DestinationScene;
use App\Enums\PhotoLicence;
use App\Models\Destination;
use App\Models\Photo;

/** Les destinations, pour l'équipe : la liste, et le formulaire avec sa galerie. */
final class DestinationQuery
{
    public function __construct(
        private OfficeDestinationRepositoryInterface $destinations,
        private DestinationGalleryRepositoryInterface $galeries,
    ) {}

    public function liste(): DestinationsPageData
    {
        return new DestinationsPageData(
            $this->destinations->toutes()->map(fn (Destination $d) => DestinationRowData::fromModel($d))->all(),
        );
    }

    public function fiche(?Destination $destination): DestinationEditPageData
    {
        $galerie = $destination ? $this->galeries->galerie($destination) : collect();
        $montrees = $this->galeries->destinationsParPhoto();
        $carte = fn (Photo $p) => GalleryPhotoData::fromModel($p, $montrees[$p->id] ?? null);

        return new DestinationEditPageData(
            destination: $destination ? DestinationFormData::fromModel($destination, $this->destinations->nombreAnnonces($destination)) : null,
            galerie: $galerie->map($carte)->values()->all(),
            zones: array_map(fn (ClimateZone $z) => new OptionData($z->value, $z->label()), ClimateZone::ordered()),
            scenes: array_map(fn (DestinationScene $s) => new OptionData($s->value, $s->label()), DestinationScene::cases()),
            // La photothèque des lieux, moins ce qui est déjà dans la galerie.
            photos: $this->galeries->photothequeDeLieux($galerie->pluck('id')->all())->map($carte)->all(),
            licences: array_map(fn (PhotoLicence $l) => new OptionData($l->value, $l->label()), PhotoLicence::cases()),
        );
    }
}
