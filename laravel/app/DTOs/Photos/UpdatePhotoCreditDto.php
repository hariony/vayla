<?php

namespace App\DTOs\Photos;

use App\Enums\PhotoLicence;

/**
 * Une correction de crédit, telle que l'écran l'envoie. `null` veut dire « non
 * envoyé » pour l'auteur et la licence ; la page d'origine distingue « non
 * envoyée » (`sourceUrlFournie` faux) de « effacée » (fournie, `null`).
 *
 * Ce que la provenance permet d'appliquer, c'est `PhotoCreditEditor` qui le
 * décide : cet objet ne dit que ce qui a été demandé.
 */
final readonly class UpdatePhotoCreditDto
{
    public function __construct(
        public string $caption,
        public ?string $author = null,
        public ?PhotoLicence $licence = null,
        public bool $sourceUrlFournie = false,
        public ?string $sourceUrl = null,
    ) {}
}
