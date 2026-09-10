<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Une période refusée pour une raison métier : nuits déjà prises, période
 * hors de l'horizon du calendrier. Ce n'est pas une panne — c'est une
 * réponse, et elle doit être lisible telle quelle par le propriétaire.
 */
class CalendarRefusedException extends RuntimeException {}
