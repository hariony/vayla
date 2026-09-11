<?php

namespace App\Http\Requests\Office;

use App\Services\Office\Journal\JournalQuery;
use Illuminate\Foundation\Http\FormRequest;

/** Les paramètres de `/journal`. Une famille inconnue vaut « Tout ». */
class JournalRequest extends FormRequest
{
    public function rules(): array
    {
        return ['famille' => ['nullable', 'string', 'max:30']];
    }

    public function famille(): ?string
    {
        $famille = (string) $this->query('famille');

        return in_array($famille, JournalQuery::familles(), true) ? $famille : null;
    }
}
