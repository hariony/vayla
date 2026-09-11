<?php

namespace App\Contracts\Repositories;

use App\Models\Booking;
use App\Models\Listing;
use App\Models\Owner;
use Illuminate\Support\Collection;

/**
 * Ce que voit un propriétaire — **et seulement ce qui est à lui**.
 *
 * Être connecté dit qui l'on est, pas ce qu'on a le droit de toucher : les
 * références sont courtes et se dictent au téléphone, et sans cette portée une
 * session valide plus une référence devinée suffiraient à répondre à la place
 * d'un confrère. Chaque méthode prend donc le propriétaire, et ne rend jamais
 * ce qui porte sur le logement d'un autre.
 */
interface OwnerSpaceRepositoryInterface
{
    /** @return Collection<int, Booking> avec leur annonce et leurs messages */
    public function reservations(Owner $owner): Collection;

    /** Une réservation sur l'un de ses logements, ou `null` — y compris si elle existe chez un autre. */
    public function reservation(Owner $owner, string $reference): ?Booking;

    /** @return Collection<int, Listing> avec destination, photos et périodes fermées */
    public function annonces(Owner $owner): Collection;

    /**
     * L'un de ses logements, avec destination, photos, périodes fermées et
     * réservations — de quoi régler son calendrier. `null` y compris si
     * l'adresse existe chez un autre.
     */
    public function annonce(Owner $owner, string $slug): ?Listing;
}
