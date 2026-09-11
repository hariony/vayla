<?php

namespace App\Services\Office\Bookings;

use App\Contracts\Repositories\OfficeBookingRepositoryInterface;
use App\Data\Office\BookingRowData;
use App\Data\Office\Bookings\BookingQueuePageData;
use App\Data\Office\ListFilterData;
use App\Data\Office\PaginatedData;
use App\Data\Office\TabData;
use App\DTOs\Office\BookingQueueFilterDto;
use App\Enums\BookingQueueFilter;
use App\Models\Booking;

/** La file des réservations : ce qui attend d'abord, trié par échéance. */
final class BookingQueueQuery
{
    private const PAR_PAGE = 25;

    public function __construct(private OfficeBookingRepositoryInterface $reservations) {}

    public function page(BookingQueueFilterDto $filtre): BookingQueuePageData
    {
        $comptes = $this->reservations->comptesParFiltre();

        return new BookingQueuePageData(
            reservations: PaginatedData::fromPaginator(
                $this->reservations->paginer($filtre->filtre, $filtre->recherche, self::PAR_PAGE),
                fn (Booking $b) => BookingRowData::fromModel($b),
            ),
            onglets: array_map(
                fn (BookingQueueFilter $f) => new TabData($f->value, $f->label(), $comptes[$f->value] ?? array_sum($comptes)),
                BookingQueueFilter::cases(),
            ),
            filtre: new ListFilterData($filtre->filtre->value, $filtre->recherche ?? ''),
        );
    }
}
