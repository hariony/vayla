<?php

namespace App\Contracts\Repositories;

use App\DTOs\Content\AmenityDto;
use App\Enums\AmenityGroup;
use App\Models\Amenity;
use Illuminate\Support\Collection;

/** Le vocabulaire des équipements, vu par l'équipe. */
interface OfficeAmenityRepositoryInterface
{
    /** @return Collection<int, Amenity> dans l'ordre, avec `listings_count` */
    public function tous(): Collection;

    /** @return array<int, string> les pictogrammes déjà dessinés — un nom inventé n'a pas de tracé */
    public function icones(): array;

    public function total(): int;

    public function cleExiste(string $cle): bool;

    public function positionSuivante(AmenityGroup $rubrique): int;

    /** @return array<int, int> les identifiants de la rubrique, dans l'ordre */
    public function ordre(AmenityGroup $rubrique): array;

    public function creer(string $cle, int $position, AmenityDto $equipement): Amenity;

    public function modifier(Amenity $equipement, AmenityDto $donnees): void;

    /** @param  array<int, int>  $ids  la position vaut l'index */
    public function ordonner(array $ids): void;

    public function supprimer(Amenity $equipement): void;

    public function nombreAnnonces(Amenity $equipement): int;
}
