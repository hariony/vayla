<?php

namespace App\Data\Travellers;

use Spatie\LaravelData\Data;

/**
 * « Mes informations » du voyageur. `sejours` : ce que l'adresse rattache —
 * c'est la seule façon de comprendre pourquoi elle ne se change pas d'un clic.
 */
final class TravellerAccountData extends Data
{
    public function __construct(
        public readonly ?string $firstName,
        public readonly ?string $lastName,
        public readonly ?string $phone,
        public readonly string $email,
        public readonly int $sejours,
    ) {}
}
