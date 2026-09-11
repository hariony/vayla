<?php

namespace App\Services;

use App\Contracts\Invoices\InvoiceCalculator;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Owner;
use Illuminate\Support\Carbon;

/**
 * La facture mensuelle d'un propriétaire.
 *
 * Le modèle tient en une phrase : **Vayla n'encaisse rien pendant le séjour,
 * et facture après**. Le voyageur ne paie jamais la plateforme ; le
 * propriétaire reçoit une facture en fin de mois et règle par mobile money.
 *
 * Trois règles rendent cette facture défendable — et une facture
 * indéfendable ne se paie pas :
 *
 * 1. **Seuls les séjours confirmés par le voyageur y figurent.** Ni les
 *    réservations acceptées, ni les no-shows, ni les annulations. Le
 *    propriétaire sait qui a dormi chez lui : il peut vérifier chaque ligne
 *    sans nous croire sur parole, et Vayla ne peut pas fabriquer de séjour.
 * 2. **Les montants viennent des valeurs figées à la réservation.** Le
 *    tarif de l'annonce peut avoir changé trois fois depuis : la ligne, non.
 * 3. **Le taux est celui de la réservation.** Augmenter la commission ne
 *    s'applique jamais rétroactivement à un séjour déjà engagé.
 *
 * La facture est un **calcul**, pas encore un document stocké : tant qu'il
 * n'y a pas d'espace propriétaire pour l'afficher et en suivre le règlement,
 * la stocker créerait un état que personne ne lit.
 */
class InvoiceService implements InvoiceCalculator
{
    /**
     * Les lignes d'un mois, pour un propriétaire.
     *
     * Le mois retenu est celui de la **fin du séjour**, pas de la
     * réservation : un séjour du 28 janvier au 3 février se facture en
     * février, quand il est terminé.
     *
     * @return array<string, mixed>
     */
    public function forOwner(Owner $owner, ?Carbon $month = null): array
    {
        $month ??= Carbon::today()->subMonthNoOverflow();
        $debut = $month->copy()->startOfMonth();
        $fin = $month->copy()->endOfMonth();

        $bookings = Booking::query()
            ->with('listing')
            ->whereHas('listing', fn ($q) => $q->where('owner_id', $owner->id))
            ->where('status', BookingStatus::Completed->value)
            // **Intervalle semi-ouvert, jamais `whereBetween` sur une date.**
            // `bookings.departure` est une colonne `date`, mais SQLite est
            // faiblement typé et y range ce que Laravel écrit — `2026-08-31
            // 00:00:00`. La comparaison redevient alors une comparaison de
            // chaînes, où cette valeur est *supérieure* à la borne haute
            // `2026-08-31` parce qu'elle est plus longue : le séjour qui se
            // termine le dernier jour du mois sortait de sa facture, et
            // n'entrait pas non plus dans la suivante. PostgreSQL, lui,
            // comparait bien des dates — le défaut ne se voyait qu'aux tests,
            // et seulement les mois où la date calculée tombait sur un 31.
            // `< premier jour du mois suivant` est juste sur les deux moteurs
            // et reste indexable, ce que `whereDate()` n'aurait pas été.
            ->where('departure', '>=', $debut->toDateString())
            ->where('departure', '<', $fin->copy()->addDay()->toDateString())
            ->orderBy('departure')
            ->get();

        $lignes = $bookings->map(fn (Booking $b) => [
            'reference' => $b->reference,
            'listing' => $b->listing->title,
            'traveller' => $b->traveller,
            'arrival' => $b->arrival->toDateString(),
            'departure' => $b->departure->toDateString(),
            'nights' => $b->nights,
            'pricePerNight' => $b->price_per_night,
            'total' => $b->total,
            'rate' => (float) $b->commission_rate,
            'commission' => $b->commission(),
        ])->all();

        return [
            'owner' => [
                'name' => $owner->name,
                'phone' => $owner->phone,
                'mobileMoney' => $owner->mobile_money,
                'operator' => $owner->mobile_money_operator,
            ],
            'period' => [
                'from' => $debut->toDateString(),
                'to' => $fin->toDateString(),
                'label' => $this->label($debut),
            ],
            'lines' => $lignes,
            'stays' => count($lignes),
            'nights' => (int) $bookings->sum('nights'),
            'revenue' => (int) $bookings->sum('total'),
            'due' => (int) $bookings->sum(fn (Booking $b) => $b->commission()),
            // Écrit sur la facture, pas seulement ici : c'est ce qui permet
            // au propriétaire de la vérifier ligne à ligne.
            'basis' => 'Séjours confirmés par les voyageurs. Les réservations non confirmées ne sont pas facturées.',
        ];
    }

    /**
     * Toutes les factures d'un mois. Un propriétaire sans séjour confirmé
     * n'en reçoit pas : envoyer une facture à zéro use la relation pour rien.
     *
     * @return array<int, array<string, mixed>>
     */
    public function forMonth(?Carbon $month = null): array
    {
        return Owner::query()
            ->orderBy('name')
            ->get()
            ->map(fn (Owner $o) => $this->forOwner($o, $month))
            ->filter(fn (array $f) => $f['stays'] > 0)
            ->values()
            ->all();
    }

    /**
     * Ce que le propriétaire voit dans sa rubrique « Facturation ».
     *
     * Trois choses, et l'ordre compte :
     *
     * 1. **Le mois en cours**, qui n'est pas encore une facture — c'est ce qui
     *    s'accumule. Le cacher jusqu'au premier du mois suivant, c'est faire
     *    découvrir un montant qu'on aurait pu voir venir, et c'est exactement
     *    ce qui fait qu'une commission se sent comme un piège.
     * 2. **La dernière facture**, celle qui est à régler.
     * 3. **Les précédentes**, pour vérifier.
     *
     * Les mois **sans séjour confirmé sont écartés** de l'historique : une
     * ligne à zéro n'apprend rien et allonge une liste qu'on parcourt pour
     * retrouver un montant. Le mois en cours, lui, reste affiché même vide —
     * « rien à payer ce mois-ci » est une information, pas un vide.
     *
     * @return array<string, mixed>
     */
    public function historique(Owner $owner, int $mois = 6): array
    {
        $aujourdhui = Carbon::today();

        $precedentes = collect(range(1, max(1, $mois)))
            ->map(fn (int $recul) => $this->forOwner($owner, $aujourdhui->copy()->subMonthsNoOverflow($recul)))
            ->filter(fn (array $facture) => $facture['stays'] > 0)
            ->values()
            ->all();

        return [
            'encours' => $this->forOwner($owner, $aujourdhui),
            'factures' => $precedentes,
            'owner' => [
                'name' => $owner->name,
                'mobileMoney' => $owner->mobile_money,
                'operator' => $owner->mobile_money_operator,
            ],
        ];
    }

    private const MOIS = [
        'janvier', 'février', 'mars', 'avril', 'mai', 'juin',
        'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre',
    ];

    private function label(Carbon $debut): string
    {
        return self::MOIS[$debut->month - 1].' '.$debut->year;
    }
}
