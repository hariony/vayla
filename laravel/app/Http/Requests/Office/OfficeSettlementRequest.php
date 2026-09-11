<?php

namespace App\Http\Requests\Office;

use App\DTOs\Office\SettlementDto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

/**
 * Le règlement d'une facture. **Le montant n'est pas un champ** : c'est la
 * facture qui dit ce qui est dû — voir `InvoiceSettlements::regler()`.
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

    public function toDto(): SettlementDto
    {
        return new SettlementDto(
            ownerId: $this->integer('owner_id'),
            mois: Carbon::createFromFormat('Y-m-d', $this->string('mois')->toString().'-01')->startOfDay(),
            reference: $this->filled('reference') ? trim($this->string('reference')->toString()) : null,
        );
    }
}
