<?php

namespace App\Data\Office\Team;

use Spatie\LaravelData\Data;

/** Un membre de l'équipe. `provisoire` : son mot de passe a été vu par quelqu'un d'autre. */
final class TeamMemberData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly string $initiales,
        public readonly bool $moi,
        public readonly bool $provisoire,
        public readonly ?string $lastLoginAt,
        public readonly int $actions,
    ) {}
}
