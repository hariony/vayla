<?php

namespace App\Services;

use App\Data\DateRangeData;
use App\Data\ListingCalendarData;
use App\Models\Listing;
use Illuminate\Support\Carbon;

/**
 * Le calendrier d'un logement.
 *
 * Deux règles de comptage, et se tromper sur l'une des deux fait mentir le
 * prix affiché :
 *
 * 1. **Une nuit appartient à sa date d'arrivée.** Un séjour du 12 au 15 fait
 *    trois nuits (12, 13, 14) et libère le 15 : la date de départ n'est pas
 *    occupée. C'est pourquoi une période stockée va de `starts_on` à
 *    `ends_on` = dernière nuit occupée, jamais jusqu'au départ.
 * 2. **Le jour du départ d'un occupant est réservable** par le suivant.
 *    L'oublier retire une nuit vendable à chaque période du calendrier.
 *
 * Le service renvoie les périodes bloquées, pas la liste des jours libres :
 * douze mois de dates individuelles font trois cent soixante-cinq chaînes
 * dans chaque réponse, pour une information que trois intervalles suffisent
 * à porter.
 */
class AvailabilityService
{
    public function __construct(
        private SeasonService $seasons,
    ) {}

    /**
     * Les nuits retirées du calendrier par les réservations en cours.
     *
     * `departure` est exclue : une nuit appartient à sa date d'arrivée, et
     * le jour du départ est réservable par le voyageur suivant.
     *
     * @return array<int, array{from: string, to: string}>
     */
    private function fromBookings(Listing $listing): array
    {
        if (! $listing->relationLoaded('bookings')) {
            return [];
        }

        return $listing->bookings
            ->filter(fn ($b) => $b->status->blocksDates()
                && ! ($b->hold_expires_at !== null && $b->hold_expires_at->isPast()))
            ->map(fn ($b) => [
                'from' => $b->arrival->toDateString(),
                'to' => $b->departure->copy()->subDay()->toDateString(),
            ])
            ->values()
            ->all();
    }

    /**
     * Horizon du calendrier. Au-delà, personne ne planifie un séjour.
     *
     * Publique parce que le formulaire de blocage du propriétaire borne ses
     * dates dessus : une période posée au-delà de l'horizon serait invisible
     * dans la grille, et donc impossible à retrouver pour la libérer.
     */
    public const MOIS = 12;

    /**
     * Les périodes occupées à venir, bornées à l'horizon.
     *
     * @return list<DateRangeData>
     */
    public function blocked(Listing $listing): array
    {
        $debut = Carbon::today();
        $fin = $debut->copy()->addMonths(self::MOIS);

        $declarees = $listing->unavailabilities
            ->filter(fn ($u) => $u->ends_on->greaterThanOrEqualTo($debut)
                && $u->starts_on->lessThanOrEqualTo($fin))
            ->map(fn ($u) => [
                'from' => $u->starts_on->toDateString(),
                'to' => $u->ends_on->toDateString(),
            ]);

        // Les deux sources se valent pour le calendrier : une nuit prise est
        // une nuit prise, qu'elle vienne d'une déclaration du propriétaire
        // ou d'une réservation en cours.
        return $declarees
            ->concat($this->fromBookings($listing))
            ->filter(fn (array $p) => $p['to'] >= $debut->toDateString()
                && $p['from'] <= $fin->toDateString())
            ->map(fn (array $p) => new DateRangeData(
                // Une période commencée avant aujourd'hui est tronquée : le
                // passé n'a pas à occuper le calendrier.
                from: max($p['from'], $debut->toDateString()),
                to: min($p['to'], $fin->toDateString()),
            ))
            ->sortBy('from')
            ->values()
            ->all();
    }

    public function calendar(Listing $listing): ListingCalendarData
    {
        return new ListingCalendarData(
            blocked: $this->blocked($listing),
            from: Carbon::today()->toDateString(),
            to: Carbon::today()->addMonths(self::MOIS)->toDateString(),
            minNights: (int) $listing->min_nights,
            maxNights: $listing->max_nights,
            price: (int) $listing->price,
            season: $this->seasons->saison($listing->destination),
        );
    }
}
