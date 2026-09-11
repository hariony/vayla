<?php

namespace App\Http\Requests;

use App\DTOs\Bookings\NewBookingDto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

/**
 * Validation d'une réservation.
 *
 * Ce que ce formulaire ne demande **jamais** : aucun numéro de carte, aucun
 * compte, aucun identifiant de paiement. Vayla n'encaisse rien — l'acompte
 * se convient entre le voyageur et le propriétaire, hors plateforme. Un
 * champ de paiement ici serait à la fois inutile et un risque que le projet
 * a explicitement décidé de ne pas porter.
 *
 * Les règles métier (chevauchement, séjour minimum, capacité) ne sont pas
 * ici : elles vivent dans BookingService, parce que l'API mobile devra les
 * appliquer aussi et qu'une règle dupliquée finit par diverger.
 */
class BookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'traveller' => ['required', 'string', 'min:2', 'max:80'],
            'traveller_phone' => ['required', 'string', 'min:8', 'max:30'],
            'traveller_email' => ['nullable', 'email', 'max:120'],
            'guests' => ['required', 'integer', 'min:1', 'max:50'],
            'arrival' => ['required', 'date', 'after_or_equal:today'],
            'departure' => ['required', 'date', 'after:arrival'],
            'message' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'traveller.required' => 'Indiquez votre nom, le propriétaire en a besoin.',
            'traveller_phone.required' => 'Un numéro joignable : c\'est par là que le propriétaire vous répondra.',
            'arrival.after_or_equal' => 'La date d\'arrivée est passée.',
            'departure.after' => 'Le départ doit être après l\'arrivée.',
        ];
    }

    public function toDto(): NewBookingDto
    {
        return new NewBookingDto(
            traveller: trim($this->string('traveller')->toString()),
            travellerPhone: trim($this->string('traveller_phone')->toString()),
            travellerEmail: $this->filled('traveller_email') ? trim($this->string('traveller_email')->toString()) : null,
            guests: $this->integer('guests'),
            arrival: Carbon::parse($this->string('arrival')->toString())->toDateString(),
            departure: Carbon::parse($this->string('departure')->toString())->toDateString(),
            message: $this->filled('message') ? $this->string('message')->toString() : null,
        );
    }
}
