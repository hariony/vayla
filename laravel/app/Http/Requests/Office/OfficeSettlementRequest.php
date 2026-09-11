<?php

namespace App\Http\Requests\Office;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Le règlement d'une facture. **Le montant n'est pas un champ** : c'est la
 * facture qui dit ce qui est dû — voir `OfficeActions::reglerFacture()`.
 */
class OfficeSettlementRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'owner_id' => ['required', 'integer', 'exists:owners,id'],
            'mois' => ['required', 'date_format:Y-m'],
            // La référence de la transaction mobile money : ce qui permet de
            // la retrouver le jour où un propriétaire conteste.
            'reference' => ['nullable', 'string', 'max:60'],
        ];
    }
}
