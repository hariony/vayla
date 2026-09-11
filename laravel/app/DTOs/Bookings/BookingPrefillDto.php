<?php

namespace App\DTOs\Bookings;

/**
 * Ce que l'adresse du formulaire de réservation apporte : les dates et le
 * nombre de voyageurs choisis sur la fiche. Une suggestion — le formulaire la
 * pré-remplit, le serveur la revalide à l'envoi.
 */
final readonly class BookingPrefillDto
{
    public function __construct(
        public ?string $arrival,
        public ?string $departure,
        public ?int $guests,
    ) {}
}
