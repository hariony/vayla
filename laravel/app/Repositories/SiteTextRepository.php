<?php

namespace App\Repositories;

use App\Contracts\Repositories\SiteTextRepositoryInterface;
use App\Models\Admin;
use App\Models\SiteText;
use Illuminate\Support\Collection;

class SiteTextRepository implements SiteTextRepositoryInterface
{
    public function valeurs(): array
    {
        return SiteText::query()->pluck('value', 'key')->all();
    }

    public function modifies(): Collection
    {
        return SiteText::query()->with('admin')->get()->keyBy('key');
    }

    public function ecrire(Admin $par, string $cle, string $valeur): void
    {
        SiteText::query()->updateOrCreate(['key' => $cle], ['value' => $valeur, 'admin_id' => $par->id]);
    }

    public function effacer(string $cle): void
    {
        SiteText::query()->where('key', $cle)->delete();
    }
}
