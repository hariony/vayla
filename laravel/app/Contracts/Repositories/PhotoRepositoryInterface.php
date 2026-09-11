<?php

namespace App\Contracts\Repositories;

use App\Models\Photo;
use Illuminate\Support\Collection;

interface PhotoRepositoryInterface
{
    /** @return Collection<int, Photo> */
    public function all(): Collection;

    public function findByKey(string $key): ?Photo;

    /**
     * Les photos à créditer au pied de page : jamais celles des propriétaires,
     * et une photo téléversée par l'équipe seulement si elle illustre quelque
     * chose.
     *
     * @return Collection<int, Photo>
     */
    public function credited(): Collection;
}
