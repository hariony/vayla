<?php

namespace App\Contracts\Repositories;

use App\Models\Admin;
use App\Models\SiteText;
use Illuminate\Support\Collection;

/** Les textes du site **réécrits** par l'équipe ; l'original, lui, reste dans `SiteTextCatalog`. */
interface SiteTextRepositoryInterface
{
    /** @return array<string, string> clé → texte réécrit */
    public function valeurs(): array;

    /** @return Collection<string, SiteText> par clé, avec qui l'a réécrit */
    public function modifies(): Collection;

    public function ecrire(Admin $par, string $cle, string $valeur): void;

    /** Le texte retombe sur son original. */
    public function effacer(string $cle): void;
}
