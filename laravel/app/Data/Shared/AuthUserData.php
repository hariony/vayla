<?php

namespace App\Data\Shared;

use App\Models\User;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;

/**
 * Le voyageur connecté, tel que **chaque** écran le reçoit (`auth.user`).
 * **Une projection explicite, jamais le modèle** : le modèle entier publiait,
 * pour un propriétaire, l'adresse exacte et l'état de vérification dans le
 * `data-page` de chaque page.
 */
final class AuthUserData extends Data
{
    public function __construct(
        #[MapOutputName('first_name')]
        public readonly ?string $firstName,
        #[MapOutputName('last_name')]
        public readonly ?string $lastName,
        public readonly ?string $name,
        public readonly string $email,
        public readonly ?string $phone,
    ) {}

    public static function fromModel(User $u): self
    {
        return new self($u->first_name, $u->last_name, $u->name, $u->email, $u->phone);
    }
}
