<?php

namespace App\Services\StayRequests;

use App\Contracts\Repositories\StayRequestRepositoryInterface;
use App\Data\Office\ListFilterData;
use App\Data\Office\PaginationData;
use App\Data\Office\TabData;
use App\Data\StayRequests\StayRequestItemData;
use App\Data\StayRequests\StayRequestQueuePageData;
use App\DTOs\StayRequests\StayRequestFilterDto;
use App\Enums\StayRequestStatus;
use App\Models\StayRequest;

/** Lire la file des demandes de séjour, onglet par statut. Ne modifie rien. */
final class StayRequestQueueQuery
{
    private const PAR_PAGE = 30;

    public function __construct(private StayRequestRepositoryInterface $demandes) {}

    public function page(StayRequestFilterDto $filtre): StayRequestQueuePageData
    {
        $page = $this->demandes->paginer($filtre->statut, $filtre->recherche, self::PAR_PAGE);

        return new StayRequestQueuePageData(
            demandes: collect($page->items())->map(fn (StayRequest $d) => StayRequestItemData::fromModel($d))->values()->all(),
            meta: PaginationData::fromPaginator($page),
            onglets: array_map(
                fn (StayRequestStatus $s) => new TabData($s->value, $s->onglet(), $this->demandes->compter($s)),
                StayRequestStatus::cases(),
            ),
            filtre: new ListFilterData($filtre->statut->value, $filtre->recherche),
        );
    }

    /** Le compteur de la colonne du back-office : ce qui attend quelqu'un. */
    public function nouvelles(): int
    {
        return $this->demandes->compter(StayRequestStatus::New);
    }
}
