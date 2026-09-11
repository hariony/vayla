<?php

namespace App\Http\Requests\Office;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Les réglages. Des bornes larges mais réelles : un taux de change à 50 000
 * ou une commission à 90 % ne sont pas des réglages, ce sont des fautes de
 * frappe qui s'afficheraient sur toutes les fiches.
 */
class OfficeSettingsRequest extends FormRequest
{
    public function rules(): array
    {
        return $this->routeIs('office.settings.rate')
            ? [
                'eur_rate' => ['required', 'numeric', 'min:1000', 'max:20000'],
                'eur_rate_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            ]
            : [
                'commission' => ['required', 'numeric', 'min:0', 'max:30'],
            ];
    }

    public function messages(): array
    {
        return [
            'eur_rate.min' => 'Un euro vaut plusieurs milliers d’ariary : vérifiez le nombre de zéros.',
            'eur_rate.max' => 'Ce taux semble trop élevé : vérifiez le nombre de zéros.',
            'eur_rate_date.before_or_equal' => 'Le taux porte la date où il a été relevé : pas une date future.',
            'commission.max' => 'Trente pour cent au plus.',
        ];
    }
}
