<?php

namespace App\Services\Office\Listings;

use App\Contracts\Repositories\OfficeListingRepositoryInterface;
use App\Data\Office\ListFilterData;
use App\Data\Office\ListingRowData;
use App\Data\Office\Listings\ListingQueuePageData;
use App\Data\Office\PaginatedData;
use App\Data\Office\TabData;
use App\DTOs\Office\ListingQueueFilterDto;
use App\Enums\ListingStatus;
use App\Models\Listing;

/** La file des annonces : « À vérifier » d'abord, la plus ancienne en tête. */
final class ListingQueueQuery
{
    private const PAR_PAGE = 25;

    /** L'ordre des onglets : le travail avant le reste. */
    private const ONGLETS = [ListingStatus::Submitted, ListingStatus::Published, ListingStatus::Draft, ListingStatus::Archived];

    public function __construct(private OfficeListingRepositoryInterface $annonces) {}

    public function page(ListingQueueFilterDto $filtre): ListingQueuePageData
    {
        $comptes = $this->annonces->comptesParStatut();

        return new ListingQueuePageData(
            annonces: PaginatedData::fromPaginator(
                $this->annonces->paginer($filtre->statut, $filtre->recherche, self::PAR_PAGE),
                fn (Listing $l) => ListingRowData::fromModel($l),
            ),
            onglets: [
                new TabData('toutes', 'Toutes', array_sum($comptes)),
                ...array_map(fn (ListingStatus $s) => new TabData($s->value, $s->libelleFile(), $comptes[$s->value]), self::ONGLETS),
            ],
            filtre: new ListFilterData($filtre->statut->value ?? 'toutes', $filtre->recherche ?? ''),
        );
    }
}
