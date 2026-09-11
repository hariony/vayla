<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Une photo refusée — illisible, trop petite, trop lourde. **Le message est
 * écrit pour la personne qui l'envoie** et se montre tel quel : « il faut au
 * moins 1 200 pixels » plutôt qu'un « fichier invalide » qui laisse chercher.
 */
class PhotoRefusedException extends RuntimeException {}
