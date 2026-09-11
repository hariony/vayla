<?php

namespace App\Data\Office\Texts;

use Spatie\LaravelData\Data;

/** Un groupe de textes, et le lien vers l'endroit du site où ils s'affichent. */
final class SiteTextGroupData extends Data
{
    /** @param  list<SiteTextFieldData>  $textes */
    public function __construct(
        public readonly string $cle,
        public readonly string $titre,
        public readonly ?string $note,
        public readonly string $lien,
        public readonly array $textes,
    ) {}
}
