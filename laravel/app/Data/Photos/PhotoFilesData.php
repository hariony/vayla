<?php

namespace App\Data\Photos;

use Spatie\LaravelData\Data;

/** Ce qu'une photo occupe sur le disque : son poids, tous paliers, et lesquels existent. */
final class PhotoFilesData extends Data
{
    /** @param  array<int, int>  $paliers */
    public function __construct(
        public readonly int $poids,
        public readonly array $paliers,
    ) {}
}
