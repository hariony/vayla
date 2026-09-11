<?php

namespace App\Data;

use Spatie\LaravelData\Data;

/**
 * Le récapitulatif des confirmations : une barre par point vérifiable.
 *
 * C'est ce qui remplace la note sur cinq. « 6 voyageurs sur 6 ont confirmé
 * que les photos correspondent » dit quelque chose ; « 4,8 étoiles » ne dit
 * rien — on ne sait pas si le 0,2 manquant vient d'une douche froide ou
 * d'une adresse fausse.
 *
 * `flagged` est publié à côté de `confirmed`, sur la même ligne. Une barre
 * qui n'afficherait que les confirmations serait une moyenne étoilée
 * déguisée en faits.
 */
class ConfirmationSummaryData extends Data
{
    /** @param  list<ConfirmationPointData>  $points  seulement ceux sur lesquels quelqu'un s'est prononcé */
    public function __construct(
        public readonly int $stays,
        public readonly int $nights,
        public readonly array $points,
        public readonly bool $demo,
    ) {}
}
