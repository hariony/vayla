<?php

namespace App\Data\StayRequests;

use Spatie\LaravelData\Data;

/** Ce que le formulaire de `/demande` sait déjà : la recherche en cours, et le voyageur s'il est connecté. */
final class StayRequestInitialData extends Data
{
    public function __construct(
        public readonly string $destination,
        public readonly string $arrival,
        public readonly string $departure,
        public readonly int $guests,
        public readonly string $name,
        public readonly string $email,
        public readonly string $phone,
    ) {}
}
