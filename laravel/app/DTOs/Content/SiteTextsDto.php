<?php

namespace App\DTOs\Content;

/**
 * Les textes d'un groupe, tels que l'équipe les a saisis — nettoyés : un
 * champ vidé vaut `''`, c'est-à-dire « rétablir l'original ».
 */
final readonly class SiteTextsDto
{
    /** @param  array<string, string>  $textes  clé du catalogue → texte */
    public function __construct(
        public string $groupe,
        public array $textes,
    ) {}
}
