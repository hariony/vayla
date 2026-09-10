<?php

namespace App\Exceptions;

use RuntimeException;

class ListingNotFoundException extends RuntimeException
{
    public function __construct(string $slug)
    {
        parent::__construct("Aucun logement ne correspond à « {$slug} ».");
    }
}
