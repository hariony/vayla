<?php

namespace App\Data\StayRequests;

use App\Models\StayRequest;
use Spatie\LaravelData\Data;

/** Le récapitulatif affiché après l'envoi : à qui, et par quel canal on répondra. */
final class SentStayRequestData extends Data
{
    public function __construct(
        public readonly string $nom,
        public readonly string $canal,
        public readonly string $contact,
    ) {}

    public static function fromModel(StayRequest $d): self
    {
        return new self(
            nom: $d->name,
            canal: $d->phone ? 'whatsapp' : 'email',
            contact: (string) ($d->phone ?? $d->email),
        );
    }
}
