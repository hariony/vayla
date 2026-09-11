<?php

namespace App\Repositories;

use App\Contracts\Repositories\SocialAccountRepositoryInterface;
use App\DTOs\Auth\SocialIdentityDto;
use App\Models\SocialAccount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class SocialAccountRepository implements SocialAccountRepositoryInterface
{
    public function lien(string $modele, SocialIdentityDto $identite): ?SocialAccount
    {
        return SocialAccount::query()
            ->where('compte_type', $modele)
            ->where('provider', $identite->provider->value)
            ->where('provider_user_id', $identite->id)
            ->first();
    }

    public function rafraichir(SocialAccount $lien, SocialIdentityDto $identite): void
    {
        $lien->fill(array_filter([
            'name' => $identite->name,
            'avatar_url' => $identite->avatar,
            'email' => $identite->email,
        ]) + ['email_verified' => $identite->verifie])->save();

        // Le compte Vayla ne prend un nom que s'il n'en avait pas : il est
        // demandé plus tard, et l'utilisateur a pu le corriger.
        if (! $lien->compte->name && $identite->name) {
            $lien->compte->forceFill(['name' => $identite->name])->save();
        }
    }

    public function compteParEmail(string $modele, string $email): ?Model
    {
        return $modele::query()->where('email', $email)->first();
    }

    public function creerCompte(string $modele, SocialIdentityDto $identite): Model
    {
        $compte = $modele::create(['name' => $identite->name, 'email' => $identite->email]);

        if ($identite->email && $identite->verifie) {
            $compte->forceFill(['email_verified_at' => Carbon::now()])->save();
        }

        return $compte;
    }

    public function lier(Model $compte, SocialIdentityDto $identite): void
    {
        $compte->socialAccounts()->firstOrCreate(
            ['provider' => $identite->provider->value, 'provider_user_id' => $identite->id],
            [
                'email' => $identite->email,
                'name' => $identite->name,
                'avatar_url' => $identite->avatar,
                'email_verified' => $identite->verifie,
            ]
        );
    }
}
