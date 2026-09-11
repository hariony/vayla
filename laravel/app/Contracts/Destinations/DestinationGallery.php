<?php

namespace App\Contracts\Destinations;

use App\Models\Destination;
use App\Models\Photo;

/**
 * La galerie d'une destination. **La position 0 est la couverture**, et
 * `destinations.photo_id` n'en est que la copie : `synchroniserCouverture()`
 * est son seul écrivain — l'atlas, l'accueil et l'API lisent cette colonne.
 */
interface DestinationGallery
{
    /** Ajoute la photo au bout de la galerie ; elle devient la couverture si la galerie était vide. */
    public function ajouter(Destination $destination, Photo $photo): void;

    /** Des positions sans trou, de 0 à n − 1. */
    public function renumeroter(Destination $destination): void;

    public function synchroniserCouverture(Destination $destination): void;
}
