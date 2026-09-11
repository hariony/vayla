<?php

namespace App\Data\Bookings;

use Spatie\LaravelData\Data;

/**
 * Une ligne de boîte : une réservation qui porte un fil. Elle répond, dans
 * l'ordre où on se le demande : est-ce que ça m'attend (`nonLu`), de quoi ça
 * parle, qu'est-ce qui a été dit en dernier — **même si c'est moi** — et quand.
 */
final class ConversationData extends Data
{
    public function __construct(
        public readonly string $reference,
        public readonly ?string $sujet,
        public readonly ?string $listing,
        public readonly ?string $place,
        public readonly string $arrival,
        public readonly string $departure,
        public readonly string $statut,
        public readonly string $statutLabel,
        public readonly ?string $auteur,
        public readonly ?string $auteurLabel,
        public readonly ?string $extrait,
        public readonly ?string $quand,
        public readonly bool $nonLu,
        public readonly int $messages,
    ) {}
}
