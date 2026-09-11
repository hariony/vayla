<?php

namespace App\Http\Requests\Office;

use App\DTOs\Office\BookingQueueFilterDto;
use App\Enums\BookingQueueFilter;
use App\Http\Requests\Office\Concerns\ReadsSearch;
use Illuminate\Foundation\Http\FormRequest;

/** Les paramètres de `/reservations`. Un onglet inconnu vaut « En attente ». */
class BookingQueueRequest extends FormRequest
{
    use ReadsSearch;

    public function rules(): array
    {
        return ['filtre' => ['nullable', 'string', 'max:20'], 'q' => ['nullable', 'string', 'max:200']];
    }

    public function toDto(): BookingQueueFilterDto
    {
        return new BookingQueueFilterDto(
            BookingQueueFilter::tryFrom((string) $this->query('filtre', 'attente')) ?? BookingQueueFilter::Attente,
            $this->recherche(),
        );
    }
}
