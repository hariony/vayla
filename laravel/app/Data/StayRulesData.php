<?php

namespace App\Data;

use App\Models\Listing;
use Spatie\LaravelData\Data;

/**
 * Le règlement du séjour — le bloc « À savoir ».
 *
 * Il vient de colonnes et non d'un paragraphe libre : « pas d'animaux » doit
 * pouvoir devenir un filtre le jour où un voyageur le demande, et un texte
 * ne se filtre pas. Le propriétaire décrit, il ne rédige pas les règles.
 *
 * `cancellation` n'est pas une donnée : **Vayla n'encaisse rien**, il n'y a
 * donc pas de politique d'annulation à afficher. Le dire est plus honnête
 * que d'inventer une colonne vide pour ressembler aux autres.
 */
class StayRulesData extends Data
{
    public function __construct(
        public readonly string $checkInFrom,
        public readonly string $checkOutBefore,
        public readonly int $minNights,
        public readonly ?int $maxNights,
        public readonly int $guests,
        public readonly bool $pets,
        public readonly bool $smoking,
        public readonly bool $events,
    ) {}

    public static function fromModel(Listing $listing): self
    {
        return new self(
            checkInFrom: substr((string) $listing->check_in_from, 0, 5),
            checkOutBefore: substr((string) $listing->check_out_before, 0, 5),
            minNights: $listing->min_nights,
            maxNights: $listing->max_nights,
            guests: $listing->guests,
            pets: $listing->pets_allowed,
            smoking: $listing->smoking_allowed,
            events: $listing->events_allowed,
        );
    }
}
