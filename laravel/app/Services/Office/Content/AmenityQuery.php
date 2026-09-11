<?php

namespace App\Services\Office\Content;

use App\Contracts\Repositories\OfficeAmenityRepositoryInterface;
use App\Data\Office\Content\AmenitiesPageData;
use App\Data\Office\Content\AmenityRowData;
use App\Data\Office\Content\AmenityRubricData;
use App\Enums\AmenityGroup;
use App\Models\Amenity;

/** Le vocabulaire des équipements, rubrique par rubrique. */
final class AmenityQuery
{
    public function __construct(private OfficeAmenityRepositoryInterface $equipements) {}

    public function page(): AmenitiesPageData
    {
        $parRubrique = $this->equipements->tous()->groupBy(fn (Amenity $a) => $a->group->value);

        return new AmenitiesPageData(
            rubriques: array_map(fn (AmenityGroup $g) => new AmenityRubricData(
                value: $g->value,
                label: $g->label(),
                equipements: ($parRubrique[$g->value] ?? collect())->map(fn (Amenity $a) => AmenityRowData::fromModel($a))->values()->all(),
            ), AmenityGroup::cases()),
            icones: $this->equipements->icones(),
            total: $this->equipements->total(),
        );
    }
}
