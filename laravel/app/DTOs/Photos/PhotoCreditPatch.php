<?php

namespace App\DTOs\Photos;

use App\Enums\PhotoLicence;

/**
 * Ce qui sera **réellement écrit** sur la photo, une fois les droits de sa
 * provenance appliqués. Un champ à `null` n'est pas touché ; la page
 * d'origine a son drapeau, parce qu'on peut vouloir l'effacer.
 */
final readonly class PhotoCreditPatch
{
    public function __construct(
        public string $caption,
        public ?string $author = null,
        public ?PhotoLicence $licence = null,
        public bool $toucherSource = false,
        public ?string $sourceUrl = null,
    ) {}
}
