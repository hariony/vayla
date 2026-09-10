<?php

namespace App\Services\Verification;

use RuntimeException;

/**
 * L'envoi du code n'a pas abouti.
 *
 * Ce n'est pas une panne à cacher : l'écran doit le dire, parce que
 * l'utilisateur attend un message qui n'arrivera pas. Le texte est écrit pour
 * lui, jamais pour les journaux.
 */
class CodeSendingFailed extends RuntimeException {}
