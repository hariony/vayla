<?php

namespace App\Http\Requests\Office;

use App\DTOs\StayRequests\StayRequestFilterDto;
use App\Enums\StayRequestStatus;
use Illuminate\Foundation\Http\FormRequest;

/** Les paramètres de `/demandes` : un statut inconnu retombe sur « Nouvelles », jamais une erreur. */
class StayRequestQueueRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'onglet' => ['nullable', 'string', 'max:30'],
            'q' => ['nullable', 'string', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->query->set('q', mb_substr(trim((string) $this->query('q', '')), 0, 100));
    }

    public function toDto(): StayRequestFilterDto
    {
        return new StayRequestFilterDto(
            statut: StayRequestStatus::tryFrom((string) $this->query('onglet')) ?? StayRequestStatus::New,
            recherche: (string) $this->query('q', ''),
        );
    }
}
