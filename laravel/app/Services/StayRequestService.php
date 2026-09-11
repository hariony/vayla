<?php

namespace App\Services;

use App\Models\Destination;
use App\Models\StayRequest;
use App\Models\User;

/**
 * Déposer une demande de séjour « dans l'autre sens ».
 *
 * **Elle n'engage personne** : aucune nuit bloquée, aucun propriétaire
 * prévenu automatiquement. L'équipe la lit dans le back-office, cherche, et
 * répond — sur WhatsApp d'abord. C'est la promesse de l'accueil : « on
 * sollicite les propriétaires de la zone, on vérifie ce qui remonte ».
 */
class StayRequestService
{
    /** @param  array<string, mixed>  $donnees  validées par `StayRequestRequest` */
    public function deposer(array $donnees, ?User $voyageur): StayRequest
    {
        $destination = ! empty($donnees['destination'])
            ? Destination::query()->where('slug', $donnees['destination'])->first()
            : null;

        return StayRequest::create([
            'destination_id' => $destination?->id,
            'place' => trim((string) ($donnees['place'] ?? '')) ?: null,
            'arrival' => $donnees['arrival'] ?? null,
            'departure' => $donnees['departure'] ?? null,
            'guests' => (int) $donnees['guests'],
            'budget' => $donnees['budget'] ?? null,
            'name' => trim($donnees['name']),
            'email' => $donnees['email'] ?? null,
            'phone' => $donnees['phone'] ?? null,
            'message' => trim((string) ($donnees['message'] ?? '')) ?: null,
            'user_id' => $voyageur?->id,
        ]);
    }
}
