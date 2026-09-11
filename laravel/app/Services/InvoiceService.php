<?php

namespace App\Services;

use App\Contracts\Invoices\InvoiceCalculator;
use App\Contracts\Repositories\BookingRepositoryInterface;
use App\Contracts\Repositories\OwnerRepositoryInterface;
use App\Data\Invoices\InvoiceData;
use App\Data\Invoices\InvoiceHistoryData;
use App\Data\Invoices\InvoiceLineData;
use App\Data\Invoices\InvoiceOwnerData;
use App\Data\Invoices\InvoicePeriodData;
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
    private const MOIS = [
        'janvier', 'février', 'mars', 'avril', 'mai', 'juin',
        'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre',
    ];

    public function __construct(
        private BookingRepositoryInterface $reservations,
        private OwnerRepositoryInterface $proprietaires,
    ) {}

    public function forOwner(Owner $owner, ?Carbon $month = null): InvoiceData
    {
        $month ??= Carbon::today()->subMonthNoOverflow();
        $debut = $month->copy()->startOfMonth();
        $fin = $month->copy()->endOfMonth();

        $sejours = $this->reservations->effectuesDuProprietaire($owner, $debut, $fin->copy()->addDay());

        return new InvoiceData(
            owner: InvoiceOwnerData::fromModel($owner),
            period: new InvoicePeriodData($debut->toDateString(), $fin->toDateString(), self::MOIS[$debut->month - 1].' '.$debut->year),
            lines: $sejours->map(fn (Booking $b) => InvoiceLineData::fromModel($b))->values()->all(),
            stays: $sejours->count(),
            nights: (int) $sejours->sum('nights'),
            revenue: (int) $sejours->sum('total'),
            due: (int) $sejours->sum(fn (Booking $b) => $b->commission()),
            // Écrit sur la facture, pas seulement ici : c'est ce qui permet
            // au propriétaire de la vérifier ligne à ligne.
            basis: 'Séjours confirmés par les voyageurs. Les réservations non confirmées ne sont pas facturées.',
        );
    }

    /**
     * Les factures d'un mois, **seulement celles qui portent un séjour** :
     * envoyer une facture à zéro use la relation pour rien.
     *
     * @return list<InvoiceData>
     */
    public function forMonth(?Carbon $month = null): array
    {
        return $this->proprietaires->tous()
            ->map(fn (Owner $o) => $this->forOwner($o, $month))
            ->filter(fn (InvoiceData $f) => $f->stays > 0)
            ->values()
            ->all();
    }

    public function historique(Owner $owner, int $mois = 6): InvoiceHistoryData
    {
        $aujourdhui = Carbon::today();

        $precedentes = collect(range(1, max(1, $mois)))
            ->map(fn (int $recul) => $this->forOwner($owner, $aujourdhui->copy()->subMonthsNoOverflow($recul)))
            ->filter(fn (InvoiceData $facture) => $facture->stays > 0)
            ->values()
            ->all();

        return new InvoiceHistoryData(
            encours: $this->forOwner($owner, $aujourdhui),
            factures: $precedentes,
            owner: InvoiceOwnerData::fromModel($owner),
        );
    }
}
