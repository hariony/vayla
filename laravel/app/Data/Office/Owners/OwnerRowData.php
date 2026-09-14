<?php

namespace App\Data\Office\Owners;

use App\Data\Office\OwnerCardData;
use App\Data\Office\PhoneData;
use App\Models\Owner;
use Spatie\LaravelData\Data;

/** Un propriétaire dans la file : sa carte, et ce qu'il a en ligne ou en attente. */
final class OwnerRowData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly ?string $email,
        public readonly ?string $city,
        public readonly ?string $portrait,
        public readonly ?PhoneData $telephone,
        public readonly bool $verified,
        public readonly bool $isDemo,
        public readonly int $listings,
        public readonly int $enLigne,
        public readonly int $aVerifier,
        public readonly int $bookings,
        // D'où il vient : la publicité, le message ou l'appel qui l'a inscrit.
        public readonly ?string $source,
    ) {}

    public static function fromModel(Owner $o): self
    {
        return new self(...[
            ...OwnerCardData::fromModel($o)->champs(),
            'listings' => (int) $o->listings_count,
            'enLigne' => (int) $o->en_ligne_count,
            'aVerifier' => (int) $o->a_verifier_count,
            'bookings' => (int) $o->bookings_count,
            'source' => $o->source,
        ]);
    }
}
