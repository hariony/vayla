<?php

namespace App\Services\Office;

use App\Contracts\Repositories\OfficeBookingRepositoryInterface;
use App\Contracts\Repositories\OfficeListingRepositoryInterface;
use App\Contracts\Repositories\OfficeWhatsAppRepositoryInterface;
use App\Data\Office\Shell\OfficeCountersData;
use App\Enums\BookingStatus;
use App\Enums\ListingStatus;
use App\Services\StayRequests\StayRequestQueueQuery;

/**
 * Les compteurs de la colonne : annonces à vérifier, réservations en attente,
 * messages WhatsApp à envoyer, demandes de séjour nouvelles. Affichés sur
 * chaque écran, ils évitent de revenir au tableau de bord pour savoir s'il
 * reste du travail.
 */
final class OfficeCountersQuery
{
    public function __construct(
        private OfficeListingRepositoryInterface $annonces,
        private OfficeBookingRepositoryInterface $reservations,
        private OfficeWhatsAppRepositoryInterface $whatsapp,
        private StayRequestQueueQuery $demandes,
    ) {}

    public function compter(): OfficeCountersData
    {
        return new OfficeCountersData(
            annonces: $this->annonces->nombreAuStatut(ListingStatus::Submitted),
            reservations: $this->reservations->nombreAuStatut(BookingStatus::Pending),
            whatsapp: $this->whatsapp->nombre(envoyes: false),
            demandes: $this->demandes->nouvelles(),
        );
    }
}
