<?php

namespace App\Data\Owners;

use App\Models\Unavailability;
use Spatie\LaravelData\Data;

/**
 * Une période fermée par le propriétaire — elle se rouvre d'un bouton. `to`
 * est la dernière nuit occupée, telle qu'elle est stockée : le front affiche à
 * côté le jour de libération, c'est là que se joue la compréhension de la règle.
 */
final class DeclaredPeriodData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $from,
        public readonly string $to,
        public readonly int $nights,
        public readonly ?string $reason,
        public readonly ?string $reasonLabel,
    ) {}

    public static function fromModel(Unavailability $u): self
    {
        return new self(
            id: $u->id,
            from: $u->starts_on->toDateString(),
            to: $u->ends_on->toDateString(),
            nights: (int) $u->starts_on->diffInDays($u->ends_on) + 1,
            reason: $u->reason?->value,
            reasonLabel: $u->reason?->label(),
        );
    }
}
