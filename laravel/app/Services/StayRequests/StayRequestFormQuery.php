<?php

namespace App\Services\StayRequests;

use App\Contracts\Repositories\DestinationRepositoryInterface;
use App\Data\OptionData;
use App\Data\StayRequests\SentStayRequestData;
use App\Data\StayRequests\StayRequestFormPageData;
use App\Data\StayRequests\StayRequestInitialData;
use App\DTOs\StayRequests\StayRequestPrefillDto;
use App\Models\Destination;
use App\Models\User;

/**
 * La page `/demande` : la recherche en cours qui arrive avec le clic, et —
 * pour un voyageur connecté — son nom et ses coordonnées. On ne redemande pas
 * ce qu'on sait.
 */
final class StayRequestFormQuery
{
    public function __construct(private DestinationRepositoryInterface $destinations) {}

    public function page(StayRequestPrefillDto $recherche, ?User $voyageur, ?SentStayRequestData $envoyee): StayRequestFormPageData
    {
        $destinations = $this->destinations->all()->sortBy('name')->values();

        // Une destination inconnue est oubliée, pas refusée : c'est une suggestion.
        $connue = $destinations->contains(fn (Destination $d) => $d->slug === $recherche->destination);

        return new StayRequestFormPageData(
            destinations: $destinations->map(fn (Destination $d) => new OptionData($d->slug, $d->name, $d->region))->all(),
            initial: new StayRequestInitialData(
                destination: $connue ? $recherche->destination : '',
                arrival: $recherche->arrival,
                departure: $recherche->departure,
                guests: $recherche->guests,
                name: (string) ($voyageur?->name ?? ''),
                email: (string) ($voyageur?->email ?? ''),
                phone: (string) ($voyageur?->phone ?? ''),
            ),
            envoyee: $envoyee,
        );
    }
}
