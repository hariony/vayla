<?php

namespace App\Services\Office\Owners;

use App\Contracts\Invoices\InvoiceCalculator;
use App\Contracts\Office\JournalReader;
use App\Contracts\Repositories\InvoiceSettlementRepositoryInterface;
use App\Contracts\Repositories\OfficeBookingRepositoryInterface;
use App\Contracts\Repositories\OfficeOwnerRepositoryInterface;
use App\Data\Office\BookingRowData;
use App\Data\Office\ListingRowData;
use App\Data\Office\Owners\OwnerDetailData;
use App\Data\Office\Owners\OwnerDetailPageData;
use App\Data\Office\Owners\OwnerInvoicesData;
use App\Models\Booking;
use App\Models\Listing;
use App\Models\Owner;
use App\Services\Office\Invoices\InvoiceLines;

/** La fiche d'un propriétaire : ses annonces, ses séjours, ses factures, son journal. */
final class OwnerDetailQuery
{
    public function __construct(
        private OfficeOwnerRepositoryInterface $proprietaires,
        private OfficeBookingRepositoryInterface $reservations,
        private InvoiceCalculator $factures,
        private InvoiceSettlementRepositoryInterface $reglements,
        private InvoiceLines $lignes,
        private JournalReader $journal,
    ) {}

    public function page(Owner $owner): OwnerDetailPageData
    {
        $owner = $this->proprietaires->pourFiche($owner);

        return new OwnerDetailPageData(
            proprietaire: OwnerDetailData::fromModel($owner),
            annonces: $owner->listings->map(fn (Listing $l) => ListingRowData::fromModel($l, avecProprietaire: false))->all(),
            reservations: $this->reservations->dernieresDuProprietaire($owner, 12)->map(fn (Booking $b) => BookingRowData::fromModel($b))->all(),
            factures: $this->factures($owner),
            journal: $this->journal->pour($owner),
        );
    }

    private function factures(Owner $owner): OwnerInvoicesData
    {
        $reglements = $this->reglements->duProprietaire($owner);
        $historique = $this->factures->historique($owner, 12);

        return new OwnerInvoicesData(
            encours: $this->lignes->resume($historique['encours'], null),
            passees: array_map(
                fn (array $f) => $this->lignes->resume($f, $reglements[substr($f['period']['from'], 0, 7)] ?? null),
                $historique['factures'],
            ),
        );
    }
}
