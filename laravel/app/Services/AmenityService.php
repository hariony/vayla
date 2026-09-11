<?php

namespace App\Services;

use App\Data\AmenityData;
use App\Data\AmenityGroupData;
use App\Enums\AmenityGroup;
use App\Models\Amenity;
use App\Contracts\Repositories\AmenityRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Le vocabulaire des équipements, mis en forme.
 *
 * Deux sorties, une seule source : la liste complète pour le formulaire du
 * propriétaire, et le sous-ensemble filtrable pour le panneau de recherche.
 * Si les deux divergeaient, un propriétaire pourrait cocher un équipement
 * sur lequel personne ne peut chercher.
 */
class AmenityService
{
    public function __construct(
        private AmenityRepositoryInterface $repository,
    ) {}

    /** Tout le vocabulaire, groupé — ce que coche un propriétaire. */
    public function catalogue(): array
    {
        return $this->group($this->repository->all());
    }

    /** Les seuls équipements qui portent un filtre de recherche. */
    public function filters(): array
    {
        return $this->group($this->repository->filterable());
    }

    /**
     * @param  Collection<int, Amenity>  $amenities
     * @return array<int, AmenityGroupData>
     */
    private function group(Collection $amenities): array
    {
        $byGroup = $amenities->groupBy(fn ($a) => $a->group->value);

        $groups = [];

        foreach (AmenityGroup::ordered() as $group) {
            $rows = $byGroup->get($group->value);

            if (! $rows || $rows->isEmpty()) {
                continue;
            }

            $groups[] = AmenityGroupData::fromEnum(
                $group,
                $rows->map(fn ($a) => AmenityData::fromModel($a))->values()->all()
            );
        }

        return $groups;
    }
}
