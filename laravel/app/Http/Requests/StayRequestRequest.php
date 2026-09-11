<?php

namespace App\Http\Requests;

use App\Rules\TelephoneValide;
use App\Support\Telephone;
use Illuminate\Foundation\Http\FormRequest;

/**
 * La demande de séjour « dans l'autre sens ».
 *
 * **Seul ce qui permet de répondre est obligatoire** : un nom, et un moyen de
 * joindre — WhatsApp de préférence, sinon une adresse e-mail. Le reste —
 * destination, dates, budget — aide l'équipe à chercher, mais « je ne sais pas
 * encore » est une réponse valable : on la lui demandera au téléphone.
 *
 * Les dates vont **par paire**, comme dans le moteur de recherche : une date
 * d'arrivée sans départ ne dit pas combien de nuits chercher.
 *
 * `site` est un champ piège, caché aux humains : un robot qui remplit tout le
 * remplit aussi.
 */
class StayRequestRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'destination' => ['nullable', 'string', 'exists:destinations,slug'],
            'place' => ['nullable', 'string', 'max:120'],
            'arrival' => ['nullable', 'required_with:departure', 'date_format:Y-m-d', 'after_or_equal:today'],
            'departure' => ['nullable', 'required_with:arrival', 'date_format:Y-m-d', 'after:arrival'],
            'guests' => ['required', 'integer', 'min:1', 'max:30'],
            'budget' => ['nullable', 'integer', 'min:10000', 'max:20000000'],
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'phone' => ['nullable', 'required_without:email', 'string', 'max:40', TelephoneValide::mobile()],
            'email' => ['nullable', 'required_without:phone', 'email', 'max:190'],
            'message' => ['nullable', 'string', 'max:1500'],
            'site' => ['prohibited'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($numero = Telephone::depuis($this->input('phone'))) {
            $this->merge(['phone' => $numero->e164()]);
        }

        if ($this->filled('email')) {
            $this->merge(['email' => mb_strtolower(trim((string) $this->input('email')))]);
        }

        // « 150 000 », « 150.000 Ar » : un budget se tape comme on le dit.
        if ($this->filled('budget')) {
            $this->merge(['budget' => (int) preg_replace('/\D+/', '', (string) $this->input('budget')) ?: null]);
        }
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Votre nom : c’est à lui que les propriétaires répondront.',
            'phone.required_without' => 'Un numéro WhatsApp ou une adresse e-mail : sans l’un des deux, nous ne pouvons pas vous répondre.',
            'email.required_without' => 'Un numéro WhatsApp ou une adresse e-mail : sans l’un des deux, nous ne pouvons pas vous répondre.',
            'email.email' => 'Cette adresse e-mail ne semble pas complète.',
            'arrival.required_with' => 'Une date d’arrivée avec la date de départ — ou aucune des deux.',
            'departure.required_with' => 'Une date de départ avec la date d’arrivée — ou aucune des deux.',
            'arrival.after_or_equal' => 'L’arrivée ne peut pas être dans le passé.',
            'departure.after' => 'Le départ doit venir après l’arrivée.',
            'budget.min' => 'Un budget par nuit, en ariary : 10 000 Ar au moins.',
            'destination.exists' => 'Choisissez une destination de la liste, ou décrivez le lieu dans le champ d’à côté.',
        ];
    }
}
