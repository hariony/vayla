<?php

namespace App\Data\Photos;

use Spatie\LaravelData\Data;

/** Un endroit où une photo apparaît : une galerie de destination ou d'annonce, avec son lien au back-office. */
final class PhotoUsageData extends Data
{
    public function __construct(
        public readonly string $type,
        public readonly int $id,
        public readonly string $label,
        public readonly string $href,
    ) {}

    public static function destination(int $id, string $nom, bool $couverture): self
    {
        return new self('destination', $id, $nom.($couverture ? ' — couverture' : ''), "/destinations/{$id}");
    }

    public static function annonce(int $id, string $titre, bool $couverture): self
    {
        return new self('annonce', $id, $titre.($couverture ? ' — couverture' : ''), "/annonces/{$id}");
    }
}
