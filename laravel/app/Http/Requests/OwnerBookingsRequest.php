<?php

namespace App\Http\Requests;

use App\Enums\BookingStatus;
use Illuminate\Foundation\Http\FormRequest;

/** Le filtre de l'historique des réservations. Un état inconnu vaut « Toutes » : une adresse périmée ouvre la liste. */
class OwnerBookingsRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }

    public function statut(): ?BookingStatus
    {
        $statut = $this->query('statut');

        return is_string($statut) ? BookingStatus::tryFrom($statut) : null;
    }
}
