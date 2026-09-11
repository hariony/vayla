<?php

namespace App\Http\Requests\Office;

use App\DTOs\Office\StatsFilterDto;
use App\Enums\StatsPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Les paramètres de `/statistiques`. **Une adresse périmée ne fait jamais
 * échouer l'écran** : une période inconnue vaut douze mois.
 *
 * La démonstration est incluse par défaut tant qu'elle existe — sinon l'écran
 * serait vide aujourd'hui — et seul `demo=0` la retire.
 */
class StatsRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $periode = $this->query('periode');

        $this->merge([
            'periode' => StatsPeriod::depuis(is_scalar($periode) ? (int) $periode : 0)->value,
            'demo' => $this->query('demo') !== '0',
        ]);
    }

    public function rules(): array
    {
        return [
            'periode' => ['required', Rule::enum(StatsPeriod::class)],
            'demo' => ['boolean'],
        ];
    }

    public function toDto(): StatsFilterDto
    {
        return new StatsFilterDto(
            periode: StatsPeriod::from($this->integer('periode')),
            avecDemo: $this->boolean('demo'),
        );
    }
}
