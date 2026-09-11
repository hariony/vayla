<?php

namespace App\Http\Requests\Office;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

/**
 * Le mois de `/facturation` : `AAAA-MM`, jamais dans le futur. **Le mois
 * dernier par défaut** — celui dont les factures viennent de partir. Une
 * saisie illisible retombe sur le défaut.
 */
class InvoiceMonthRequest extends FormRequest
{
    public function rules(): array
    {
        return ['mois' => ['nullable', 'string', 'max:7']];
    }

    public function mois(): Carbon
    {
        $saisie = (string) $this->query('mois');

        if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $saisie)) {
            $mois = Carbon::createFromFormat('Y-m-d', $saisie.'-01')->startOfDay();

            if ($mois->lte(Carbon::today()->startOfMonth())) {
                return $mois;
            }
        }

        return Carbon::today()->subMonthNoOverflow()->startOfMonth();
    }
}
