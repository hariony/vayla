<?php

namespace App\Http\Requests;

use App\DTOs\Bookings\BookingPrefillDto;
use Illuminate\Foundation\Http\FormRequest;

/**
 * L'adresse du formulaire de réservation : les dates et le nombre de
 * voyageurs choisis sur la fiche. **Une suggestion** — elle pré-remplit, elle
 * ne fait jamais échouer la page ; c'est l'envoi (`BookingRequest`) qui
 * valide pour de bon.
 */
class BookingFormRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }

    public function toDto(): BookingPrefillDto
    {
        $texte = fn (string $cle) => is_string($v = $this->query($cle)) && $v !== '' ? $v : null;

        return new BookingPrefillDto(
            arrival: $texte('arrivee'),
            departure: $texte('depart'),
            guests: $this->integer('voyageurs') ?: null,
        );
    }
}
