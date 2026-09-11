<?php

namespace App\DTOs\Office;

use App\Enums\BookingQueueFilter;

/** La file des réservations : un onglet, une recherche. */
final readonly class BookingQueueFilterDto
{
    public function __construct(
        public BookingQueueFilter $filtre = BookingQueueFilter::Attente,
        public ?string $recherche = null,
    ) {}
}
