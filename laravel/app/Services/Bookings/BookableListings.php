<?php

namespace App\Services\Bookings;

use App\Contracts\Repositories\ListingRepositoryInterface;
use App\Exceptions\ListingNotFoundException;
use App\Models\Listing;
use App\Services\Support\DemoMode;

/**
 * L'annonce qu'on réserve, retrouvée par son adresse — **sous le même drapeau
 * de démonstration que la fiche** : sans lui, une URL directe laisserait
 * réserver une annonce fictive alors que le catalogue s'est vidé.
 */
final class BookableListings
{
    public function __construct(
        private ListingRepositoryInterface $annonces,
        private DemoMode $demo,
    ) {}

    /** @throws ListingNotFoundException */
    public function trouver(string $slug): Listing
    {
        return $this->annonces->findBySlug($slug, $this->demo->actif()) ?? throw new ListingNotFoundException($slug);
    }
}
