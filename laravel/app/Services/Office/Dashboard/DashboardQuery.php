<?php

namespace App\Services\Office\Dashboard;

use App\Contracts\Office\JournalReader;
use App\Contracts\Repositories\OfficeBookingRepositoryInterface;
use App\Contracts\Repositories\OfficeListingRepositoryInterface;
use App\Contracts\Repositories\OfficeOwnerRepositoryInterface;
use App\Contracts\Repositories\OfficeTravellerRepositoryInterface;
use App\Contracts\Repositories\OfficeWhatsAppRepositoryInterface;
use App\Data\Office\BookingRowData;
use App\Data\Office\Dashboard\DashboardFiguresData;
use App\Data\Office\Dashboard\DashboardPageData;
use App\Data\Office\Dashboard\TodoData;
use App\Data\Office\Dashboard\TrustRungCountData;
use App\Data\Office\Invoices\InvoiceSummaryData;
use App\Data\Office\ListingRowData;
use App\Enums\BookingQueueFilter;
use App\Enums\ListingStatus;
use App\Enums\TrustLevel;
use App\Models\Booking;
use App\Models\Listing;
use App\Services\Office\Invoices\InvoiceLines;
use Illuminate\Support\Carbon;

/**
 * **Ce qui attend quelqu'un, avant ce qui se mesure.** On n'ouvre pas le
 * back-office pour regarder des courbes : on l'ouvre parce qu'une annonce
 * attend son appel, qu'une demande expire ou qu'un message WhatsApp n'est pas
 * parti. Les chiffres viennent après, et ne sont que des comptes.
 */
final class DashboardQuery
{
    /** Au-delà, une demande qui attend est à relancer par téléphone. */
    public const HEURES_URGENCE = 12;

    public function __construct(
        private OfficeListingRepositoryInterface $annonces,
        private OfficeBookingRepositoryInterface $reservations,
        private OfficeOwnerRepositoryInterface $proprietaires,
        private OfficeTravellerRepositoryInterface $voyageurs,
        private OfficeWhatsAppRepositoryInterface $whatsapp,
        private InvoiceLines $factures,
        private JournalReader $journal,
    ) {}

    public function page(): DashboardPageData
    {
        $statuts = $this->annonces->comptesParStatut();
        $echelle = $this->annonces->echelleEnLigne();
        $aVerifier = $this->annonces->aVerifier(5);

        return new DashboardPageData(
            aTraiter: $this->aTraiter($statuts, $aVerifier->first()),
            annonces: $aVerifier->map(fn (Listing $l) => ListingRowData::fromModel($l))->all(),
            demandes: $this->reservations->urgentes(self::HEURES_URGENCE, 5)->map(fn (Booking $b) => BookingRowData::fromModel($b))->all(),
            chiffres: $this->chiffres($statuts),
            echelle: array_map(fn (TrustLevel $n) => new TrustRungCountData($n->value, $n->label(), $echelle[$n->value]), TrustLevel::cases()),
            activite: $this->journal->dernieres(8),
        );
    }

    /**
     * @param  array<string, int>  $statuts
     * @return array<int, TodoData>
     */
    private function aTraiter(array $statuts, ?Listing $plusAncienne): array
    {
        $attente = $this->reservations->comptesParFiltre()[BookingQueueFilter::Attente->value];
        $moisDernier = Carbon::today()->subMonthNoOverflow()->startOfMonth();
        $impayees = count(array_filter($this->factures->du($moisDernier), fn (InvoiceSummaryData $l) => ! $l->settlement));

        return [
            new TodoData('annonces', $statuts[ListingStatus::Submitted->value], 'Annonces à vérifier', '/annonces?statut=submitted',
                $plusAncienne ? 'La plus ancienne attend depuis '.$plusAncienne->updated_at->diffForHumans(null, true).'.' : 'Aucune fiche en attente.'),
            new TodoData('demandes', $this->reservations->nombreExpirantSous(self::HEURES_URGENCE),
                'Demandes qui expirent sous '.self::HEURES_URGENCE.' h', '/reservations?filtre=attente',
                $attente.' demande'.($attente > 1 ? 's' : '').' en attente au total.'),
            new TodoData('whatsapp', $this->whatsapp->nombre(envoyes: false), 'Messages WhatsApp à envoyer', '/whatsapp',
                'Un clic ouvre WhatsApp, le message est déjà écrit.'),
            new TodoData('numeros', $this->proprietaires->nombreAAppeler(), 'Numéros à vérifier par appel', '/proprietaires?filtre=a-verifier',
                'Propriétaires dont une fiche attend, numéro pas encore confirmé.'),
            new TodoData('factures', $impayees, 'Factures de '.$moisDernier->translatedFormat('F Y').' non réglées',
                '/facturation?mois='.$moisDernier->format('Y-m'), 'Réglées par mobile money : consignez chaque règlement reçu.'),
        ];
    }

    /** @param  array<string, int>  $statuts */
    private function chiffres(array $statuts): DashboardFiguresData
    {
        $debutDuMois = Carbon::today()->startOfMonth();

        return new DashboardFiguresData(
            enLigne: $statuts[ListingStatus::Published->value],
            proprietaires: $this->proprietaires->total(),
            voyageurs: $this->voyageurs->total(),
            reservationsMois: $this->reservations->nombreCreeesDepuis($debutDuMois),
            sejoursMois: $this->reservations->nombreSejoursEffectuesDepuis($debutDuMois),
            commissionEncours: array_sum(array_map(fn (InvoiceSummaryData $l) => $l->due, $this->factures->du($debutDuMois))),
        );
    }
}
