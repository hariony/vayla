<?php

namespace App\Data;

use App\Enums\ConfirmationPoint;
use App\Models\StayConfirmation;
use Spatie\LaravelData\Data;

/**
 * Un séjour confirmé, tel qu'il s'affiche.
 *
 * Aucune note nulle part : `points` porte ce qui a été confirmé, `flagged`
 * ce qui a été signalé, et les deux voyagent ensemble. Un client qui ne
 * lirait que `points` afficherait une fiche complaisante — c'est pourquoi
 * `flagged` n'est jamais absent, seulement vide.
 */
class ConfirmationData extends Data
{
    /**
     * @param  array<int, string>  $points
     * @param  array<int, string>  $flagged
     */
    public function __construct(
        public readonly string $traveller,
        public readonly ?string $from,
        public readonly int $nights,
        public readonly string $month,
        public readonly array $points,
        public readonly array $flagged,
        public readonly ?string $comment,
        public readonly ?string $mismatch,
        public readonly bool $demo,
    ) {}

    private const MOIS = [
        'janvier', 'février', 'mars', 'avril', 'mai', 'juin',
        'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre',
    ];

    public static function fromModel(StayConfirmation $c): self
    {
        return new self(
            traveller: $c->traveller,
            from: $c->traveller_from,
            nights: $c->nights,
            // Le mois, jamais le jour : on ne dit pas quand un logement
            // précis était occupé par quelqu'un de nommé.
            month: self::MOIS[$c->stayed_on->month - 1].' '.$c->stayed_on->year,
            points: $c->points ?? [],
            flagged: $c->flagged ?? [],
            comment: $c->comment,
            mismatch: $c->mismatch,
            demo: $c->is_demo,
        );
    }

    /** Le libellé long d'un point, pour que le front ne le réécrive pas. */
    public static function label(string $key): string
    {
        return ConfirmationPoint::from($key)->label();
    }
}
