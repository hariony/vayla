<?php

namespace App\Http\Requests\Office;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Le motif d'une décision : renvoyer une fiche, archiver, annuler un séjour.
 *
 * **Obligatoire quand quelqu'un d'autre le lira** — le propriétaire à qui l'on
 * renvoie sa fiche, les deux parties d'un séjour annulé. Dix caractères au
 * moins : « non » n'explique rien, et le motif est la seule chose que la
 * personne aura sous les yeux.
 */
class OfficeReasonRequest extends FormRequest
{
    public function rules(): array
    {
        $requis = $this->routeIs('office.listings.archive') ? 'nullable' : 'required';

        // Le motif d'une annulation est rangé dans `bookings.closed_reason`,
        // une colonne de 255 caractères : au-delà, PostgreSQL refuserait
        // l'écriture après que le fil a déjà reçu le message.
        $plafond = $this->routeIs('office.bookings.cancel') ? 250 : 1000;

        return ['reason' => [$requis, 'string', 'min:10', 'max:'.$plafond]];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Écrivez le motif : c’est ce que la personne lira.',
            'reason.min' => 'Dix caractères au moins : le motif doit expliquer, pas seulement trancher.',
        ];
    }
}
