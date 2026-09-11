<?php

namespace App\Services\Office;

use RuntimeException;

/**
 * Trop d'essais de connexion au back-office. Porte le délai, pour que l'écran
 * dise **quand** réessayer plutôt que « réessayez plus tard ».
 */
class OfficeThrottled extends RuntimeException
{
    public function __construct(public readonly int $secondes)
    {
        parent::__construct("Trop d'essais. Réessayez dans {$secondes} secondes.");
    }
}
