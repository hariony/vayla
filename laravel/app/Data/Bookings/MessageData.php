<?php

namespace App\Data\Bookings;

use App\Enums\MessageAuthor;
use App\Models\BookingMessage;
use Spatie\LaravelData\Data;

/**
 * Un message du fil d'une réservation. `moi` dit de quel côté il se range :
 * le même fil est rendu par l'écran du propriétaire, par celui du voyageur et
 * par le back-office, et c'est la seule chose qui les distingue.
 */
final class MessageData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $author,
        public readonly string $authorLabel,
        public readonly bool $moi,
        public readonly string $body,
        public readonly string $at,
    ) {}

    public static function fromModel(BookingMessage $m, MessageAuthor $lecteur): self
    {
        return new self($m->id, $m->author->value, $m->author->label(), $m->author === $lecteur, $m->body, $m->created_at->toIso8601String());
    }
}
