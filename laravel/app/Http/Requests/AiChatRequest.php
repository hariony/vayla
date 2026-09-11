<?php

namespace App\Http\Requests;

use App\DTOs\Ai\AiMessageDto;
use Illuminate\Foundation\Http\FormRequest;

/** Une question posée au modèle. Bornée : une requête sans limite de taille coûte au jeton. */
class AiChatRequest extends FormRequest
{
    public function rules(): array
    {
        return ['message' => ['required', 'string', 'max:4000']];
    }

    public function toDto(): AiMessageDto
    {
        return AiMessageDto::utilisateur($this->string('message')->toString());
    }
}
