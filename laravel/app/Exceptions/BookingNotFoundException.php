<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Aucune réservation ne porte cette référence. Un fait métier, pas une
 * panne : il sort en 404, comme une annonce ou une destination introuvable.
 */
class BookingNotFoundException extends RuntimeException
{
    public function __construct(string $reference)
    {
        parent::__construct("Aucune réservation ne correspond à « {$reference} ».");
    }
}
