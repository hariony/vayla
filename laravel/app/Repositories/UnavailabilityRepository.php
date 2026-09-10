<?php

namespace App\Repositories;

use App\Data\SejourData;
use App\Enums\BlockReason;
use App\Models\Listing;
use App\Models\Unavailability;
use App\Repositories\Contracts\UnavailabilityRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class UnavailabilityRepository implements UnavailabilityRepositoryInterface
{
    public function aVenir(Listing $listing): Collection
    {
        return Unavailability::query()
            ->where('listing_id', $listing->id)
            // Une période dont la dernière nuit est passée ne ferme plus
            // rien : l'afficher n'apporterait qu'une liste qui s'allonge.
            ->whereDate('ends_on', '>=', Carbon::today())
            ->orderBy('starts_on')
            ->get();
    }

    public function chevauchant(Listing $listing, SejourData $sejour): Collection
    {
        // Les deux bornes stockées sont des **nuits occupées**, inclusives.
        // La dernière nuit du séjour se lit sur `SejourData`, jamais par une
        // soustraction refaite ici.
        return Unavailability::query()
            ->where('listing_id', $listing->id)
            ->whereDate('starts_on', '<=', $sejour->derniereNuit())
            ->whereDate('ends_on', '>=', $sejour->arrival)
            ->orderBy('starts_on')
            ->get();
    }

    public function bloquer(Listing $listing, SejourData $sejour, BlockReason $motif): Unavailability
    {
        return Unavailability::create([
            'listing_id' => $listing->id,
            'starts_on' => $sejour->arrival,
            'ends_on' => $sejour->derniereNuit(),
            'reason' => $motif,
        ]);
    }

    public function liberer(Listing $listing, int $id): bool
    {
        // `listing_id` est dans la clause, pas vérifié après coup : une
        // suppression qui commence par charger la ligne finit toujours par
        // oublier le contrôle un jour de refactorisation.
        return Unavailability::query()
            ->where('listing_id', $listing->id)
            ->whereKey($id)
            ->delete() > 0;
    }
}
