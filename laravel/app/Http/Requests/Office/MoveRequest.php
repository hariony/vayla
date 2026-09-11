<?php

namespace App\Http\Requests\Office;

use App\Enums\PositionShift;
use Illuminate\Foundation\Http\FormRequest;

/** Monter ou descendre une ligne d'un cran. Un sens inconnu vaut « bas », comme avant. */
class MoveRequest extends FormRequest
{
    public function rules(): array
    {
        return ['sens' => ['nullable', 'string', 'max:10']];
    }

    public function sens(): PositionShift
    {
        return PositionShift::tryFrom((string) $this->input('sens')) ?? PositionShift::Bas;
    }
}
