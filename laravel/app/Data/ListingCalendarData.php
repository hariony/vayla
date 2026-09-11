<?php

namespace App\Data;

use Spatie\LaravelData\Data;

/**
 * Le calendrier d'un logement : ce qui est pris, l'horizon, les bornes de
 * séjour, le tarif — **et la saison**, qui voyage avec : les deux se lisent sur
 * la même grille, les séparer obligerait le front à les recoller mois par mois.
 */
final class ListingCalendarData extends Data
{
    /** @param  list<DateRangeData>  $blocked */
    public function __construct(
        public readonly array $blocked,
        public readonly string $from,
        public readonly string $to,
        public readonly int $minNights,
        public readonly ?int $maxNights,
        public readonly int $price,
        public readonly SeasonData $season,
    ) {}

    /**
     * Le même calendrier **sans bornes de séjour** : elles encadrent ce qu'un
     * voyageur réserve, pas ce qu'un propriétaire ferme — un logement qui se
     * loue trois nuits minimum doit pouvoir être fermé une seule soirée.
     */
    public function sansBornes(): self
    {
        return new self($this->blocked, $this->from, $this->to, 1, null, $this->price, $this->season);
    }
}
