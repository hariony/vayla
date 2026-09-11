<?php

namespace App\Services\Auth;

use App\DTOs\Auth\SocialIdentityDto;
use Illuminate\Support\Facades\Session;

/**
 * L'identité sociale d'un propriétaire inconnu, **gardée le temps de la fiche**.
 * Le compte n'existe pas encore au retour du fournisseur — `owners.phone` est
 * obligatoire, et le fournisseur ne le donne pas — : le lien se posera quand
 * il existera.
 */
final class PendingSocialIdentity
{
    private const CLE = 'social.identite';

    public function retenir(SocialIdentityDto $identite): void
    {
        Session::put(self::CLE, $identite->enSession());
    }

    /** L'identité retenue, retirée de la session. */
    public function reprendre(): ?SocialIdentityDto
    {
        return SocialIdentityDto::depuisSession(Session::pull(self::CLE));
    }
}
