<?php

namespace App\Http\Requests\Office;

use App\Http\Requests\Office\Concerns\ReadsSearch;
use Illuminate\Foundation\Http\FormRequest;

/** Une liste qui n'a qu'une recherche — les voyageurs. */
class OfficeSearchRequest extends FormRequest
{
    use ReadsSearch;

    public function rules(): array
    {
        return ['q' => ['nullable', 'string', 'max:200']];
    }
}
