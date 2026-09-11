<?php

namespace App\Services\Office\Travellers;

use App\Contracts\Repositories\OfficeTravellerRepositoryInterface;
use App\Data\Office\ListFilterData;
use App\Data\Office\PaginatedData;
use App\Data\Office\PhoneData;
use App\Data\Office\Travellers\TravellerRowData;
use App\Data\Office\Travellers\TravellersPageData;
use App\Models\User;

/** Les comptes voyageurs. Les séjours s'y rattachent par l'adresse, pas par une clé. */
final class TravellerQuery
{
    private const PAR_PAGE = 30;

    public function __construct(private OfficeTravellerRepositoryInterface $voyageurs) {}

    public function page(?string $recherche): TravellersPageData
    {
        return new TravellersPageData(
            voyageurs: PaginatedData::fromPaginator($this->voyageurs->paginer($recherche, self::PAR_PAGE), fn (User $u) => new TravellerRowData(
                id: $u->id,
                name: $u->name,
                email: $u->email,
                telephone: PhoneData::depuis($u->phone),
                bookings: (int) $u->bookings_count,
                createdAt: $u->created_at?->toIso8601String(),
            )),
            total: $this->voyageurs->total(),
            filtre: new ListFilterData('', $recherche ?? ''),
        );
    }
}
