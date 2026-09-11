<?php

namespace App\Data\Travellers;

use Spatie\LaravelData\Data;

/** Qui est connecté. `name` peut manquer : il n'est demandé qu'à la demande de séjour. */
final class TravellerIdentityData extends Data
{
    public function __construct(
        public readonly ?string $name,
        public readonly string $email,
    ) {}
}
