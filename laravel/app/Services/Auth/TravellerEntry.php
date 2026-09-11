<?php

namespace App\Services\Auth;

use App\Models\User;

/** Qui entre, et si son compte vient de s'ouvrir — c'est la seule fois où on le lui dit. */
final readonly class TravellerEntry
{
    public function __construct(
        public User $user,
        public bool $nouveau,
    ) {}
}
