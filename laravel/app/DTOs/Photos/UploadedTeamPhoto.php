<?php

namespace App\DTOs\Photos;

use App\Models\Destination;
use App\Models\Photo;

/** Le résultat d'un téléversement : la photo, et la destination qui l'a reçue s'il y en a une. */
final readonly class UploadedTeamPhoto
{
    public function __construct(
        public Photo $photo,
        public ?Destination $destination,
    ) {}
}
