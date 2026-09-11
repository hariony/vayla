<?php

namespace App\DTOs\Ai;

/** Un message d'une conversation avec le modèle : `user`, `assistant` ou `system`. */
final readonly class AiMessageDto
{
    public function __construct(
        public string $role,
        public string $content,
    ) {}

    public static function utilisateur(string $content): self
    {
        return new self('user', $content);
    }
}
