<?php

namespace App\Services\Office\Stats;

use App\Data\Office\Stats\CommissionData;
use App\Data\Office\Stats\CommissionDebtorData;
use App\Data\Office\Stats\CommissionFiguresData;
use App\Data\Office\Stats\CommissionMonthData;
use App\Data\Office\Stats\SeriesData;
use App\Models\Booking;
use App\Models\InvoiceSettlement;
use Illuminate\Support\Collection;

/**
 * La commission : ce qui est facturé, ce qui est reçu, **ce qui manque**, et
 * chez qui.
 *
 * Mêmes règles que la facture, pour que les deux écrans tombent sur les mêmes
 * montants : une facture est celle **d'un propriétaire pour un mois**, faite
 * de la commission des séjours effectués partis ce mois-là ; un règlement
 * solde ce mois-là. **Le mois en cours n'est pas une facture** : il
 * s'accumule, rien n'y est dû, et il n'entre ni dans le reste à recevoir ni
 * dans le recouvrement.
 */
final class CommissionStats
{
    /** Au-delà, l'écran dit combien d'autres doivent encore, et renvoie à la facturation. */
    private const DEBITEURS = 10;

    /**
     * @param  Collection<int, Booking>  $sejours  effectués, avec `listing.owner`
     * @param  Collection<int, InvoiceSettlement>  $reglements
     */
    public function rapport(MonthGrid $grille, Collection $sejours, Collection $reglements): CommissionData
    {
        $factures = $this->factures($sejours, $reglements);
        $mois = $this->parMois($grille, $sejours, $reglements, $factures);
        $actifs = array_filter($mois, fn (CommissionMonthData $m) => $m->enCours || $m->sejours > 0 || $m->reglee > 0);
        $debiteurs = $this->debiteurs($grille, $factures);

        return new CommissionData(
            chiffres: $this->chiffres($mois),
            series: [
                new SeriesData('facturee', 'Commission facturée', 'terre', array_map(fn (CommissionMonthData $m) => $m->facturee, $mois)),
                new SeriesData('reglee', 'Commission reçue', 'encre', array_map(fn (CommissionMonthData $m) => $m->reglee, $mois)),
            ],
            mois: array_values(array_reverse($actifs)),
            moisSansActivite: count($mois) - count($actifs),
            debiteurs: $debiteurs->take(self::DEBITEURS)->all(),
            autresDebiteurs: max(0, $debiteurs->count() - self::DEBITEURS),
        );
    }

    /** @return Collection<int, OwnerMonthInvoice> une par propriétaire et par mois de départ */
    private function factures(Collection $sejours, Collection $reglements): Collection
    {
        $reglementDe = $reglements->keyBy(fn (InvoiceSettlement $r) => $r->owner_id.'|'.$this->moisDu($r));

        return $sejours
            ->groupBy(fn (Booking $b) => $b->listing?->owner_id.'|'.$b->departure->format('Y-m'))
            ->map(function (Collection $lignes, string $cle) use ($reglementDe) {
                $owner = $lignes->first()->listing?->owner;
                $reglement = $reglementDe->get($cle);

                return new OwnerMonthInvoice(
                    ownerId: $owner?->id,
                    nom: $owner?->name ?? 'Logement retiré',
                    mois: $lignes->first()->departure->format('Y-m'),
                    facturee: (int) $lignes->sum(fn (Booking $b) => $b->commission()),
                    recu: (int) ($reglement?->amount ?? 0),
                    reglee: $reglement !== null,
                );
            })
            ->values();
    }

    /** @return list<CommissionMonthData> du plus ancien au mois en cours */
    private function parMois(MonthGrid $grille, Collection $sejours, Collection $reglements, Collection $factures): array
    {
        $sejoursDu = $sejours->groupBy(fn (Booking $b) => $b->departure->format('Y-m'));
        $recuDu = $reglements->groupBy(fn (InvoiceSettlement $r) => $this->moisDu($r));
        $facturesDu = $factures->groupBy(fn (OwnerMonthInvoice $f) => $f->mois);

        return array_map(fn (string $cle) => $this->mois(
            $grille,
            $cle,
            $sejoursDu->get($cle, new Collection),
            (int) $recuDu->get($cle, new Collection)->sum('amount'),
            $facturesDu->get($cle, new Collection),
        ), $grille->cles());
    }

    private function mois(MonthGrid $grille, string $cle, Collection $sejours, int $recu, Collection $factures): CommissionMonthData
    {
        $volume = (int) $sejours->sum('total');
        $facturee = (int) $sejours->sum(fn (Booking $b) => $b->commission());
        $clos = $grille->estClos($cle);

        return new CommissionMonthData(
            mois: $cle,
            label: $grille->libelleLong($cle),
            enCours: ! $clos,
            sejours: $sejours->count(),
            volume: $volume,
            taux: $volume > 0 ? round($facturee / $volume * 100, 1) : null,
            facturee: $facturee,
            reglee: $recu,
            reste: $clos ? (int) $factures->sum(fn (OwnerMonthInvoice $f) => $f->reste()) : null,
            factures: $factures->count(),
            reglees: $factures->filter(fn (OwnerMonthInvoice $f) => $f->reglee)->count(),
        );
    }

    /** @param  list<CommissionMonthData>  $mois */
    private function chiffres(array $mois): CommissionFiguresData
    {
        $tous = collect($mois);
        $clos = $tous->reject(fn (CommissionMonthData $m) => $m->enCours);
        $volume = (int) $tous->sum('volume');
        $facturee = (int) $tous->sum('facturee');
        $factureeClose = (int) $clos->sum('facturee');
        $reste = (int) $clos->sum('reste');

        return new CommissionFiguresData(
            volume: $volume,
            taux: $volume > 0 ? round($facturee / $volume * 100, 1) : null,
            facturee: $facturee,
            reglee: (int) $tous->sum('reglee'),
            reste: $reste,
            recouvrement: $factureeClose > 0 ? round(($factureeClose - $reste) / $factureeClose * 100) : null,
            factures: (int) $clos->sum('factures'),
            reglees: (int) $clos->sum('reglees'),
            enCours: (int) ($tous->first(fn (CommissionMonthData $m) => $m->enCours)?->facturee ?? 0),
        );
    }

    /** @return Collection<int, CommissionDebtorData> le plus gros reste en tête */
    private function debiteurs(MonthGrid $grille, Collection $factures): Collection
    {
        return $factures
            ->filter(fn (OwnerMonthInvoice $f) => $grille->estClos($f->mois) && $f->reste() > 0)
            ->groupBy(fn (OwnerMonthInvoice $f) => (string) $f->ownerId)
            ->map(fn (Collection $l) => new CommissionDebtorData(
                ownerId: $l->first()->ownerId,
                nom: $l->first()->nom,
                reste: (int) $l->sum(fn (OwnerMonthInvoice $f) => $f->reste()),
                mois: $l->sortBy('mois')->map(fn (OwnerMonthInvoice $f) => $grille->libelleCourt($f->mois))->values()->all(),
            ))
            ->sortByDesc(fn (CommissionDebtorData $d) => $d->reste)
            ->values();
    }

    /** « AAAA-MM » du mois qu'un règlement solde. */
    private function moisDu(InvoiceSettlement $r): string
    {
        return substr((string) $r->month, 0, 7);
    }
}
