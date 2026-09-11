<?php

namespace App\Services\Auth;

use Illuminate\Database\Eloquent\Model;

/**
 * Le compte auquel une identité sociale s'est rattachée, et s'il vient de
 * naître. `compte` est nul pour un propriétaire inconnu : il ne se crée pas
 * sans numéro, et passe par la fiche.
 */
final readonly class SocialAttachment
{
    public function __construct(
        public ?Model $compte,
        public bool $nouveau,
    ) {}
}
