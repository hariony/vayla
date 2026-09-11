<?php

namespace App\Contracts\Repositories;

use App\DTOs\Content\DestinationDto;
use App\Models\Destination;
use Illuminate\Support\Collection;

/** Les destinations, vues par l'équipe. La galerie a son propre contrat. */
interface OfficeDestinationRepositoryInterface
{
    /** @return Collection<int, Destination> par nom, avec leur couverture et `listings_count` */
    public function toutes(): Collection;

    public function nombreAnnonces(Destination $destination): int;

    public function slugExiste(string $slug): bool;

    public function creer(string $slug, DestinationDto $destination): Destination;

    public function modifier(Destination $destination, DestinationDto $donnees): void;

    public function supprimer(Destination $destination): void;
}
