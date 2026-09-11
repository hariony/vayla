<?php

namespace App\Contracts\Repositories;

use App\Models\Destination;
use App\Models\Photo;
use Illuminate\Support\Collection;

/** Le pivot `destination_photo` et la couverture recopiée dans `destinations.photo_id`. */
interface DestinationGalleryRepositoryInterface
{
    /** @return array<int, int> les photos de la galerie, dans l'ordre */
    public function photos(Destination $destination): array;

    /** Accroche une photo après la dernière position. */
    public function accrocherAuBout(Destination $destination, int $photoId): void;

    /** @param  array<int, int>  $ids  dans l'ordre voulu ; la position vaut l'index */
    public function ordonner(Destination $destination, array $ids): void;

    public function poserCouverture(Destination $destination, ?int $photoId): void;

    /** @return Collection<int, Photo> les photos de la galerie, dans l'ordre */
    public function galerie(Destination $destination): Collection;

    public function contient(Destination $destination, int $photoId): bool;

    public function detacher(Destination $destination, int $photoId): void;

    /** Une photo encore montrée par au moins une destination. */
    public function estMontree(int $photoId): bool;

    /**
     * Une photo qui peut illustrer une destination : **une vraie photographie
     * de lieu** — Commons ou téléversée par l'équipe, jamais une image générée
     * ni une photo des annonces de démonstration.
     */
    public function photoDeLieu(int $photoId): ?Photo;

    /**
     * @param  array<int, int>  $exclues
     * @return Collection<int, Photo> les photos de lieux disponibles, téléversées d'abord
     */
    public function photothequeDeLieux(array $exclues): Collection;

    /** @return array<int, string> le nom d'une destination qui montre chaque photo, par identifiant de photo */
    public function destinationsParPhoto(): array;
}
