<?php

namespace App\Services\Office\Owners;

use App\Contracts\Repositories\OfficeOwnerRepositoryInterface;
use App\Data\Office\ListFilterData;
use App\Data\Office\Owners\OwnerQueuePageData;
use App\Data\Office\Owners\OwnerRowData;
use App\Data\Office\PaginatedData;
use App\Data\Office\TabData;
use App\DTOs\Office\OwnerQueueFilterDto;
use App\Models\Owner;

/** La liste des propriétaires, par nom. */
final class OwnerQueueQuery
{
    private const PAR_PAGE = 25;

    public function __construct(private OfficeOwnerRepositoryInterface $proprietaires) {}

    public function page(OwnerQueueFilterDto $filtre): OwnerQueuePageData
    {
        return new OwnerQueuePageData(
            proprietaires: PaginatedData::fromPaginator(
                $this->proprietaires->paginer($filtre->numeroAVerifier, $filtre->recherche, self::PAR_PAGE),
                fn (Owner $o) => OwnerRowData::fromModel($o),
            ),
            onglets: [
                new TabData('tous', 'Tous', $this->proprietaires->total()),
                new TabData('a-verifier', 'Numéro à vérifier', $this->proprietaires->nombreNumeroAVerifier()),
            ],
            filtre: new ListFilterData($filtre->numeroAVerifier ? 'a-verifier' : 'tous', $filtre->recherche ?? ''),
        );
    }
}
