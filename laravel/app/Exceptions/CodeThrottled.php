<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Trop de codes demandés.
 *
 * Ce n'est pas une panne : c'est la borne qui empêche qu'un inconnu fasse
 * envoyer vingt messages sur l'adresse ou le téléphone de quelqu'un d'autre.
 * Le message porte le délai, parce qu'« réessayez plus tard » fait recliquer.
 */
class CodeThrottled extends RuntimeException
{
    public function __construct(string $message, public readonly int $secondes)
    {
        parent::__construct($message);
    }
}
