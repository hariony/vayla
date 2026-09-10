<?php

namespace App\Enums;

use App\Models\Owner;
use App\Models\User;

/**
 * Vers quel espace une connexion sociale mène.
 *
 * **L'URL de rappel est unique et figée chez le fournisseur** : elle est
 * déclarée une fois dans la console Google, Meta ou Apple, et ne peut pas
 * varier selon le bouton cliqué. L'espace visé voyage donc en **session**,
 * posé au départ et relu au retour — le même endroit que le `state` CSRF, qui
 * survit au même aller-retour.
 *
 * Sans ça, il faudrait déclarer deux URL de rappel par fournisseur, à tenir
 * synchronisées dans trois consoles.
 */
enum EspaceSocial: string
{
    case Voyageur = 'voyageur';
    case Proprietaire = 'proprietaire';

    /** La garde d'authentification correspondante. */
    public function garde(): ?string
    {
        return $this === self::Proprietaire ? 'proprietaire' : null;
    }

    /** @return class-string */
    public function modele(): string
    {
        return $this === self::Proprietaire ? Owner::class : User::class;
    }

    /** Où l'on atterrit une fois connecté. */
    public function destination(): string
    {
        return $this === self::Proprietaire ? 'owner.home' : 'traveller.bookings';
    }

    /** Où l'on revient quand ça échoue : l'écran d'où l'on venait. */
    public function retour(): string
    {
        return $this === self::Proprietaire ? 'owner.login' : 'access.client';
    }
}
