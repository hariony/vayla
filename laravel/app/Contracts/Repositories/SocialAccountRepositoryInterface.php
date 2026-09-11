<?php

namespace App\Contracts\Repositories;

use App\DTOs\Auth\SocialIdentityDto;
use App\Models\SocialAccount;
use Illuminate\Database\Eloquent\Model;

/**
 * Les identités sociales, rattachées à un compte voyageur **ou** propriétaire :
 * `$modele` est la classe du compte — la même personne peut être les deux
 * avec le même compte Google, et ce sont deux identités distinctes.
 */
interface SocialAccountRepositoryInterface
{
    public function lien(string $modele, SocialIdentityDto $identite): ?SocialAccount;

    /** Met à jour le lien, et nomme le compte s'il n'avait pas encore de nom. */
    public function rafraichir(SocialAccount $lien, SocialIdentityDto $identite): void;

    public function compteParEmail(string $modele, string $email): ?Model;

    /** L'adresse n'est marquée vérifiée que si le fournisseur l'atteste. */
    public function creerCompte(string $modele, SocialIdentityDto $identite): Model;

    public function lier(Model $compte, SocialIdentityDto $identite): void;
}
