<?php

namespace App\Http\Requests\Office;

use Illuminate\Foundation\Http\FormRequest;

/** L'ordre d'une galerie, envoyé par le glisser-déposer ou les flèches. La première est la couverture. */
class PhotoOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'max:60'],
            'ids.*' => ['integer'],
        ];
    }

    /** @return array<int, int> */
    public function ids(): array
    {
        return array_map('intval', $this->validated('ids'));
    }
}
