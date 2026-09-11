<?php

namespace App\DTOs\StayRequests;

/**
 * Une demande de séjour « dans l'autre sens », validée. Seuls le nom et un
 * moyen de joindre sont sûrs d'être là ; le reste peut attendre l'appel.
 */
final readonly class SubmitStayRequestDto
{
    public function __construct(
        public string $name,
        public int $guests,
        public ?string $phone = null,
        public ?string $email = null,
        public ?string $destinationSlug = null,
        public ?string $place = null,
        public ?string $arrival = null,
        public ?string $departure = null,
        public ?int $budget = null,
        public ?string $message = null,
        public ?int $userId = null,
    ) {}
}
