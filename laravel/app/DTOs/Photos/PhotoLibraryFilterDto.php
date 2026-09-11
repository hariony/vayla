<?php

namespace App\DTOs\Photos;

use App\Enums\PhotoLibraryTab;

/** Ce que regarde la photothèque : un onglet, une recherche, une photo ouverte. */
final readonly class PhotoLibraryFilterDto
{
    public function __construct(
        public PhotoLibraryTab $onglet = PhotoLibraryTab::Lieux,
        public string $recherche = '',
        public ?int $photoOuverte = null,
    ) {}
}
