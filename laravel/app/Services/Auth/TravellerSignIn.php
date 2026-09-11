<?php

namespace App\Services\Auth;

use App\Contracts\Repositories\TravellerRepositoryInterface;

/**
 * **Trouver ou créer**, et c'est toute la mécanique de la porte unique :
 * `/inscription` et `/connexion/client` postent au même endroit, et c'est le
 * code qui décide. Il vient de prouver que celui qui le saisit relève cette
 * boîte : que le compte existe déjà ou non ne change rien à ce qu'il a le
 * droit d'ouvrir.
 */
final class TravellerSignIn
{
    public function __construct(
        private TravellerRepositoryInterface $voyageurs,
    ) {}

    public function entrer(string $email): TravellerEntry
    {
        $user = $this->voyageurs->parEmail($email);
        $nouveau = $user === null;
        $user ??= $this->voyageurs->creer($email);

        // Le code **est** la vérification de l'adresse : la redemander par un
        // second message serait la même preuve, une deuxième fois.
        $this->voyageurs->marquerEmailVerifie($user);

        return new TravellerEntry($user, $nouveau);
    }
}
