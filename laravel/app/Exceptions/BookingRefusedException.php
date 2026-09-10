<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Une réservation refusée pour une raison métier : dates prises, séjour trop
 * court, capacité dépassée. Ce n'est pas une panne — c'est une réponse.
 */
class BookingRefusedException extends RuntimeException {}
