<?php

namespace App\Services\Destinations;

use App\Contracts\Destinations\DestinationGallery;
use App\Contracts\Repositories\DestinationGalleryRepositoryInterface;
use App\Models\Destination;
use App\Models\Photo;
use Illuminate\Support\Facades\DB;

/**
 * La galerie d'une destination : ajouter au bout, renuméroter, recopier la
 * couverture. **Le seul écrivain de `destinations.photo_id`** — la photo de
 * position 0, recopiée à chaque geste : deux écrivains, et la couverture de
 * l'atlas finirait par ne plus être la première photo de la page.
 */
final class DestinationGalleryService implements DestinationGallery
{
    public function __construct(private DestinationGalleryRepositoryInterface $galeries) {}

    public function ajouter(Destination $destination, Photo $photo): void
    {
        DB::transaction(function () use ($destination, $photo) {
            $this->galeries->accrocherAuBout($destination, $photo->id);
            $this->renumeroter($destination);
            $this->synchroniserCouverture($destination);
        });
    }

    public function renumeroter(Destination $destination): void
    {
        $this->galeries->ordonner($destination, $this->galeries->photos($destination));
    }

    public function synchroniserCouverture(Destination $destination): void
    {
        $this->galeries->poserCouverture($destination, $this->galeries->photos($destination)[0] ?? null);
    }
}
