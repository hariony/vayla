<?php

namespace App\DTOs\Photos;

use App\Enums\PhotoLicence;

/** Le crédit d'une photo de lieu téléversée : ce qui s'affiche au pied de chaque page. */
final readonly class PhotoCreditDto
{
    public function __construct(
        public string $caption,
        public string $author,
        public PhotoLicence $licence,
        public ?string $sourceUrl,
    ) {}
}
