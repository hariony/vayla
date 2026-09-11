<?php

namespace App\DTOs\Photos;

use Illuminate\Http\UploadedFile;

/**
 * Une photo que l'équipe téléverse, avec son crédit, et la destination à la
 * galerie de laquelle l'ajouter aussitôt — ou aucune.
 */
final readonly class UploadTeamPhotoDto
{
    public function __construct(
        public UploadedFile $fichier,
        public PhotoCreditDto $credit,
        public ?int $destinationId = null,
    ) {}
}
