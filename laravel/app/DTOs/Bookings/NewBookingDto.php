<?php

namespace App\DTOs\Bookings;

/**
 * Une demande de séjour, telle que le voyageur l'a saisie — validée et
 * nettoyée. Les dates sont en `AAAA-MM-JJ` ; `departure` est le jour du départ,
 * pas la dernière nuit.
 */
final readonly class NewBookingDto
{
    public function __construct(
        public string $traveller,
        public string $travellerPhone,
        public ?string $travellerEmail,
        public int $guests,
        public string $arrival,
        public string $departure,
        public ?string $message = null,
    ) {}
}
