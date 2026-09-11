<?php

namespace App\Http\Requests\Office;

use App\DTOs\Office\ListingQueueFilterDto;
use App\Enums\ListingStatus;
use App\Http\Requests\Office\Concerns\ReadsSearch;
use Illuminate\Foundation\Http\FormRequest;

/** Les paramètres de `/annonces`. Un statut inconnu vaut « Toutes » : une adresse ne fait jamais échouer l'écran. */
class ListingQueueRequest extends FormRequest
{
    use ReadsSearch;

    public function rules(): array
    {
        return ['statut' => ['nullable', 'string', 'max:20'], 'q' => ['nullable', 'string', 'max:200']];
    }

    public function toDto(): ListingQueueFilterDto
    {
        return new ListingQueueFilterDto(ListingStatus::tryFrom((string) $this->query('statut')), $this->recherche());
    }
}
