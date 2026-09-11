<?php

namespace App\Http\Requests\Office;

use App\DTOs\Office\OwnerQueueFilterDto;
use App\Http\Requests\Office\Concerns\ReadsSearch;
use Illuminate\Foundation\Http\FormRequest;

/** Les paramètres de `/proprietaires`. */
class OwnerQueueRequest extends FormRequest
{
    use ReadsSearch;

    public function rules(): array
    {
        return ['filtre' => ['nullable', 'string', 'max:20'], 'q' => ['nullable', 'string', 'max:200']];
    }

    public function toDto(): OwnerQueueFilterDto
    {
        return new OwnerQueueFilterDto($this->query('filtre') === 'a-verifier', $this->recherche());
    }
}
