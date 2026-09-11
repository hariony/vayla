<?php

namespace App\Data\Auth;

use Spatie\LaravelData\Data;

/**
 * Les props de `Auth/Code`, qui sert les deux inscriptions. `attente` : les
 * secondes avant de pouvoir redemander un code — calculées par le serveur,
 * seul à savoir quand le dernier est parti.
 */
final class CodePageData extends Data
{
    public function __construct(
        public readonly string $email,
        public readonly string $action,
        public readonly string $renvoi,
        public readonly string $retour,
        public readonly int $attente,
    ) {}
}
