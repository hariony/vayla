<?php

namespace App\Contracts\Listings;

use App\Exceptions\PhotoRefusedException;
use App\Models\Listing;
use App\Models\Photo;
use Illuminate\Http\UploadedFile;

/** La galerie d'une annonce. **La position 0 est la couverture.** */
interface ListingGallery
{
    /** @throws PhotoRefusedException si l'image est illisible ou trop petite — message à montrer tel quel */
    public function ajouter(Listing $listing, UploadedFile $fichier, ?string $legende = null): Photo;

    public function retirer(Listing $listing, Photo $photo): void;

    /** @param  array<int, int>  $ids  dans l'ordre voulu */
    public function reordonner(Listing $listing, array $ids): void;
}
