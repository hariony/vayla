<?php

namespace App\Repositories;

use App\Contracts\Repositories\AdminRepositoryInterface;
use App\Models\Admin;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AdminRepository implements AdminRepositoryInterface
{
    public function nom(int $id): ?string
    {
        return Admin::query()->whereKey($id)->value('name');
    }

    public function tousParNom(): Collection
    {
        return Admin::query()->withCount('actions')->orderBy('name')->get();
    }

    public function nombre(): int
    {
        return Admin::query()->count();
    }

    public function creer(string $nom, string $email): Admin
    {
        return Admin::create(['name' => $nom, 'email' => $email]);
    }

    public function supprimer(Admin $admin): void
    {
        $admin->delete();
    }

    public function parEmail(string $email): ?Admin
    {
        return Admin::query()->where('email', $email)->first();
    }

    public function marquerConnexion(Admin $admin): void
    {
        $admin->forceFill(['last_login_at' => Carbon::now()])->save();
    }

    public function poserMotDePasse(Admin $admin, string $motDePasse, bool $choisi): void
    {
        $admin->forceFill([
            'password' => $motDePasse,
            'password_set_at' => $choisi ? Carbon::now() : null,
        ])->save();
    }
}
