<?php

namespace App\Services\StayRequests;

use App\Contracts\Repositories\DestinationRepositoryInterface;
use App\Contracts\Repositories\StayRequestRepositoryInterface;
use App\DTOs\StayRequests\SubmitStayRequestDto;
use App\Models\StayRequest;

/**
 * Déposer une demande de séjour « dans l'autre sens ».
 *
 * **Elle n'engage personne** : aucune nuit bloquée, aucun propriétaire
 * prévenu d'office. L'équipe la lit dans le back-office, cherche, et répond —
 * sur WhatsApp d'abord. C'est la promesse de l'accueil : « on sollicite les
 * propriétaires de la zone, on vérifie ce qui remonte ».
 */
final class StayRequestSubmitter
{
    public function __construct(
        private StayRequestRepositoryInterface $demandes,
        private DestinationRepositoryInterface $destinations,
    ) {}

    public function deposer(SubmitStayRequestDto $demande): StayRequest
    {
        $destination = $demande->destinationSlug ? $this->destinations->findBySlug($demande->destinationSlug) : null;

        return $this->demandes->creer($demande, $destination?->id);
    }
}
