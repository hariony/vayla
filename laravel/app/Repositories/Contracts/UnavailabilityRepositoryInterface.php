<?php

namespace App\Repositories\Contracts;

use App\Data\SejourData;
use App\Enums\BlockReason;
use App\Models\Listing;
use App\Models\Unavailability;
use Illuminate\Support\Collection;

/**
 * Les périodes déclarées par le propriétaire.
 *
 * Toutes les méthodes prennent le logement : c'est **la** portée de sécurité
 * de l'espace propriétaire. Une signature qui accepterait un identifiant de
 * période nu permettrait de libérer les dates d'un confrère avec une clé
 * valide et un identifiant deviné.
 */
interface UnavailabilityRepositoryInterface
{
    /** Les périodes à venir, la plus proche d'abord. @return Collection<int, Unavailability> */
    public function aVenir(Listing $listing): Collection;

    /** Les périodes déclarées qui croisent le séjour. @return Collection<int, Unavailability> */
    public function chevauchant(Listing $listing, SejourData $sejour): Collection;

    /** Bloque un séjour. `ends_on` vient de `SejourData::derniereNuit()`, jamais du départ. */
    public function bloquer(Listing $listing, SejourData $sejour, BlockReason $motif): Unavailability;

    /** Libère une période **de ce logement**. Renvoie faux si elle n'y appartient pas. */
    public function liberer(Listing $listing, int $id): bool;
}
