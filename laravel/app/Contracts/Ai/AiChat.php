<?php

namespace App\Contracts\Ai;

use App\DTOs\Ai\AiMessageDto;
use RuntimeException;

/**
 * Une conversation avec un modèle de langue. L'implémentation parle à
 * n'importe quelle API compatible OpenAI : changer `AI_BASE_URL` suffit à
 * changer de fournisseur.
 */
interface AiChat
{
    /**
     * @param  list<AiMessageDto>  $messages
     *
     * @throws RuntimeException clé absente, ou fournisseur injoignable
     */
    public function chat(array $messages, ?string $modele = null): string;
}
