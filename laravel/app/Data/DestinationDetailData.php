<?php

namespace App\Data;

use Spatie\LaravelData\Data;

/**
 * La page d'une destination.
 *
 * Une page qui ne serait qu'une liste filtrée n'aurait pas lieu d'être —
 * `/logements?destination=nosy-be` le fait déjà. Elle existe parce qu'elle
 * porte trois choses que la grille ne porte pas : **quand venir**, **comment
 * y aller**, et **jusqu'où nous sommes allés pour vérifier ce qu'on y
 * propose**.
 *
 * `trust` est la répartition par barreau, pas une moyenne. Sur un site dont
 * la promesse est la vérification, dire « 6 logements » sans dire à quel
 * niveau ne vaut rien.
 *
 * `prices` peut être vide : cinq destinations sur onze n'ont encore aucune
 * annonce, et c'est le cas majoritaire. Il se raconte, il ne se cache pas.
 */
class DestinationDetailData extends Data
{
    /**
     * @param  array<int, ListingData>  $listings
     * @param  array<int, array<string, mixed>>  $trust
     * @param  array<string, mixed>  $season
     * @param  array{min: int|null, max: int|null}  $prices
     */
    public function __construct(
        public readonly DestinationData $destination,
        public readonly AccessData $access,
        public readonly array $listings,
        public readonly array $trust,
        public readonly array $season,
        public readonly array $prices,
    ) {}
}
