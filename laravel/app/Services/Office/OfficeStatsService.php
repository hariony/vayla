<?php

namespace App\Services\Office;

use App\Enums\BookingStatus;
use App\Enums\ListingStatus;
use App\Enums\TrustLevel;
use App\Models\Booking;
use App\Models\InvoiceSettlement;
use App\Models\Listing;
use App\Models\Owner;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Les statistiques du back-office : des courbes, et **rien que des comptes
 * définis**.
 *
 * Sur un produit dont toute la promesse est la vérification, un chiffre
 * invérifiable est le premier mensonge. Chaque série dit donc ce qu'elle
 * compte, et à quelle date elle le range :
 *
 * - une **demande** se range au mois où elle a été **faite** ;
 * - un **séjour** et sa **commission** au mois du **départ** — c'est la règle
 *   de la facture, et les deux écrans doivent tomber sur les mêmes montants ;
 * - un **règlement** au mois qu'il **solde**, pas au jour où il a été reçu.
 *
 * **Le délai de réponse est une médiane, pas une moyenne** : un propriétaire
 * qui répond au bout de quarante-sept heures ne doit pas faire croire que
 * tous répondent en une journée, ni l'inverse. Et le taux de réponse ne compte
 * que les demandes **tranchées** — une demande encore en attente n'a pas
 * encore échoué.
 *
 * **Les données de démonstration se séparent d'un réglage**, et l'écran dit
 * quand elles sont incluses : des courbes nourries de réservations fictives
 * ne doivent jamais passer pour l'activité réelle.
 *
 * Le regroupement se fait **en PHP, pas en SQL** : les fonctions de date
 * diffèrent entre PostgreSQL et SQLite, et les volumes d'une plateforme qui
 * démarre tiennent largement en mémoire. Le jour où ils ne tiendront plus, ce
 * sera une vue matérialisée — et ce fichier sera le seul à changer.
 */
class OfficeStatsService
{
    public const PERIODES = [6, 12, 24];

    /** @return array<string, mixed> */
    public function rapport(int $mois, bool $avecDemo): array
    {
        $debut = Carbon::today()->startOfMonth()->subMonthsNoOverflow($mois - 1);
        $cles = collect(range(0, $mois - 1))->map(fn (int $i) => $debut->copy()->addMonthsNoOverflow($i)->format('Y-m'));
        $libelles = $cles->map(fn (string $c) => Carbon::createFromFormat('Y-m-d', "{$c}-01")->translatedFormat('M y'))->values()->all();
        $complets = $cles->map(fn (string $c) => Carbon::createFromFormat('Y-m-d', "{$c}-01")->translatedFormat('F Y'))->values()->all();

        $demandes = Booking::query()
            ->with('listing.destination')
            ->where('created_at', '>=', $debut)
            ->when(! $avecDemo, fn ($q) => $q->where('is_demo', false))
            ->get();

        $sejours = Booking::query()
            ->where('status', BookingStatus::Completed->value)
            ->where('departure', '>=', $debut->toDateString())
            ->when(! $avecDemo, fn ($q) => $q->where('is_demo', false))
            ->get();

        $reglements = InvoiceSettlement::query()
            ->where('month', '>=', $debut->toDateString())
            ->when(! $avecDemo, fn ($q) => $q->whereHas('owner', fn ($o) => $o->where('is_demo', false)))
            ->get();

        $parMois = fn (Collection $lignes, callable $date, callable $valeur) => $cles
            ->map(fn (string $c) => $lignes->filter(fn ($l) => $date($l) === $c)->sum($valeur))
            ->values()->all();

        $moisDeCreation = fn (Booking $b) => $b->created_at->format('Y-m');
        $moisDeDepart = fn (Booking $b) => $b->departure->format('Y-m');

        // ── Les demandes, par issue ─────────────────────────────────────
        $issues = [
            ['cle' => 'acceptees', 'label' => 'Acceptées', 'teinte' => 'encre', 'statuts' => [BookingStatus::Accepted, BookingStatus::Completed]],
            ['cle' => 'attente', 'label' => 'En attente', 'teinte' => 'terre', 'statuts' => [BookingStatus::Pending]],
            ['cle' => 'refusees', 'label' => 'Refusées', 'teinte' => 'gris', 'statuts' => [BookingStatus::Declined]],
            ['cle' => 'expirees', 'label' => 'Expirées sans réponse', 'teinte' => 'terre-pale', 'statuts' => [BookingStatus::Expired]],
            ['cle' => 'annulees', 'label' => 'Annulées', 'teinte' => 'filet', 'statuts' => [BookingStatus::Cancelled]],
        ];

        $seriesDemandes = collect($issues)->map(fn (array $i) => [
            'cle' => $i['cle'],
            'label' => $i['label'],
            'teinte' => $i['teinte'],
            'valeurs' => $parMois(
                $demandes->filter(fn (Booking $b) => in_array($b->status, $i['statuts'], true)),
                $moisDeCreation,
                fn () => 1,
            ),
        ])->all();

        // ── La réponse des propriétaires ────────────────────────────────
        $tranchees = fn (Collection $l) => $l->filter(fn (Booking $b) => ! in_array($b->status, [BookingStatus::Pending, BookingStatus::Cancelled], true));
        $repondues = fn (Collection $l) => $l->filter(fn (Booking $b) => $b->answered_at !== null);

        $tauxParMois = $cles->map(function (string $c) use ($demandes, $tranchees, $repondues, $moisDeCreation) {
            $duMois = $demandes->filter(fn (Booking $b) => $moisDeCreation($b) === $c);
            $t = $tranchees($duMois)->count();

            return $t ? round($repondues($tranchees($duMois))->count() / $t * 100) : null;
        })->values()->all();

        $delais = $repondues($demandes)
            ->map(fn (Booking $b) => max(0, $b->created_at->diffInMinutes($b->answered_at)) / 60)
            ->sort()->values();

        $medianeParMois = $cles->map(function (string $c) use ($demandes, $repondues, $moisDeCreation) {
            $d = $repondues($demandes->filter(fn (Booking $b) => $moisDeCreation($b) === $c))
                ->map(fn (Booking $b) => max(0, $b->created_at->diffInMinutes($b->answered_at)) / 60);

            return $d->isEmpty() ? null : round($this->mediane($d->sort()->values()), 1);
        })->values()->all();

        $totalTranchees = $tranchees($demandes)->count();

        // ── Les séjours et l'argent ─────────────────────────────────────
        $commissions = $parMois($sejours, $moisDeDepart, fn (Booking $b) => $b->commission());
        $reglees = $cles->map(fn (string $c) => (int) $reglements->filter(fn (InvoiceSettlement $r) => substr((string) $r->month, 0, 7) === $c)->sum('amount'))->values()->all();

        // ── Les comptes ─────────────────────────────────────────────────
        $voyageurs = User::query()
            ->where('created_at', '>=', $debut)
            ->when(! $avecDemo, fn ($q) => $q->where('email', 'not like', '%demo.vayla.test'))
            ->get(['created_at']);
        $proprietaires = Owner::query()
            ->where('created_at', '>=', $debut)
            ->when(! $avecDemo, fn ($q) => $q->where('is_demo', false))
            ->get(['created_at']);
        $annonces = Listing::query()
            ->where('created_at', '>=', $debut)
            ->when(! $avecDemo, fn ($q) => $q->where('is_demo', false))
            ->get(['created_at']);
        $moisDe = fn ($m) => $m->created_at->format('Y-m');

        // ── Le catalogue, aujourd'hui ───────────────────────────────────
        $catalogue = Listing::query()->when(! $avecDemo, fn ($q) => $q->where('is_demo', false));

        $parDestination = $demandes
            ->groupBy(fn (Booking $b) => $b->listing?->destination?->name ?? 'Logement retiré')
            ->map(fn (Collection $l, string $nom) => ['label' => $nom, 'valeur' => $l->count()])
            ->sortByDesc('valeur')->values()->take(10)->all();

        return [
            'periode' => ['mois' => $mois, 'choix' => self::PERIODES, 'debut' => $debut->translatedFormat('F Y')],
            'demo' => ['inclus' => $avecDemo, 'present' => Booking::query()->where('is_demo', true)->exists()],
            'mois' => ['courts' => $libelles, 'longs' => $complets],

            'chiffres' => [
                'demandes' => $demandes->count(),
                'tauxReponse' => $totalTranchees ? round($repondues($tranchees($demandes))->count() / $totalTranchees * 100) : null,
                'delaiMedian' => $delais->isEmpty() ? null : round($this->mediane($delais), 1),
                'sejours' => $sejours->count(),
                'nuits' => (int) $sejours->sum('nights'),
                'volume' => (int) $sejours->sum('total'),
                'commission' => array_sum($commissions),
                'reglee' => array_sum($reglees),
            ],

            'demandes' => $seriesDemandes,
            'reponse' => [
                ['cle' => 'taux', 'label' => 'Demandes répondues', 'teinte' => 'encre', 'valeurs' => $tauxParMois],
            ],
            'delai' => [
                ['cle' => 'mediane', 'label' => 'Délai médian de réponse', 'teinte' => 'terre', 'valeurs' => $medianeParMois],
            ],
            'sejours' => [
                ['cle' => 'sejours', 'label' => 'Séjours effectués', 'teinte' => 'encre', 'valeurs' => $parMois($sejours, $moisDeDepart, fn () => 1)],
                ['cle' => 'nuits', 'label' => 'Nuits', 'teinte' => 'terre', 'valeurs' => $parMois($sejours, $moisDeDepart, fn (Booking $b) => $b->nights)],
            ],
            'argent' => [
                ['cle' => 'facturee', 'label' => 'Commission facturée', 'teinte' => 'terre', 'valeurs' => $commissions],
                ['cle' => 'reglee', 'label' => 'Commission reçue', 'teinte' => 'encre', 'valeurs' => $reglees],
            ],
            'inscriptions' => [
                ['cle' => 'voyageurs', 'label' => 'Comptes voyageurs', 'teinte' => 'encre', 'valeurs' => $parMois($voyageurs, $moisDe, fn () => 1)],
                ['cle' => 'proprietaires', 'label' => 'Propriétaires', 'teinte' => 'terre', 'valeurs' => $parMois($proprietaires, $moisDe, fn () => 1)],
                ['cle' => 'annonces', 'label' => 'Annonces créées', 'teinte' => 'gris', 'valeurs' => $parMois($annonces, $moisDe, fn () => 1)],
            ],
            'destinations' => $parDestination,
            'statuts' => collect([ListingStatus::Published, ListingStatus::Submitted, ListingStatus::Draft, ListingStatus::Archived])
                ->map(fn (ListingStatus $s) => ['label' => $s->label(), 'valeur' => (clone $catalogue)->where('status', $s->value)->count(), 'teinte' => $s === ListingStatus::Submitted ? 'terre' : 'encre'])
                ->all(),
            'niveaux' => collect(TrustLevel::cases())
                ->map(fn (TrustLevel $n) => [
                    'label' => "{$n->value} · {$n->label()}",
                    'valeur' => (clone $catalogue)->where('status', ListingStatus::Published->value)->where('trust_level', $n->value)->count(),
                    // L'échelle est une vérification : le lagon y est chez lui,
                    // sauf au niveau 1, qui n'en est pas une.
                    'teinte' => $n->isVerified() ? 'lagon' : 'gris',
                ])->all(),
        ];
    }

    private function mediane(Collection $valeurs): float
    {
        $n = $valeurs->count();
        $milieu = intdiv($n, 2);

        return $n % 2 ? (float) $valeurs[$milieu] : ($valeurs[$milieu - 1] + $valeurs[$milieu]) / 2;
    }
}
