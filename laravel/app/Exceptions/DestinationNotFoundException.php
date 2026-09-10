<?php

namespace App\Exceptions;

use RuntimeException;

class DestinationNotFoundException extends RuntimeException
{
    public function __construct(string $slug)
    {
        parent::__construct("Aucune destination ne correspond à « {$slug} ».");
    }
}
