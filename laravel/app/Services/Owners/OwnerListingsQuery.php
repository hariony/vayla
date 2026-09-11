<?php

namespace App\Services\Owners;

use App\Contracts\Listings\ListingDrafting;
use App\Contracts\Repositories\OwnerSpaceRepositoryInterface;
use App\Data\Owners\OwnerListingFormPageData;
use App\Data\Owners\OwnerListingRowData;
use App\Data\Owners\OwnerListingsPageData;
use App\Models\Listing;
use App\Models\Owner;

/** « Mes logements », et le formulaire d'une annonce — nulle à la création. */
final class OwnerListingsQuery
{
    public function __construct(
        private OwnerSpaceRepositoryInterface $espace,
        private OwnerSpace $portee,
        private ListingDrafting $redaction,
    ) {}

    public function liste(Owner $owner): OwnerListingsPageData
    {
        return new OwnerListingsPageData(
            $this->espace->annonces($owner)->map(fn (Listing $l) => OwnerListingRowData::fromModel($l))->values()->all(),
        );
    }

    public function creation(): OwnerListingFormPageData
    {
        return OwnerListingFormPageData::depuis(null, $this->redaction->vocabulaire());
    }

    public function edition(Owner $owner, string $slug): OwnerListingFormPageData
    {
        $listing = $this->portee->logement($owner, $slug);

        return OwnerListingFormPageData::depuis($this->redaction->pourEdition($listing), $this->redaction->vocabulaire());
    }
}
