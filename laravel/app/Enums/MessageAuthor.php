<?php

namespace App\Enums;

/**
 * Qui a écrit dans le fil d'une réservation.
 *
 * **Un rôle, pas un identifiant.** Un voyageur n'a pas de compte sur Vayla —
 * c'est délibéré, on ne fait pas créer un compte pour demander un séjour — et
 * le fil doit rester lisible si la réservation change de main côté
 * propriétaire.
 *
 * `Vayla` existe pour la médiation : quand on intervient dans un échange, ça
 * doit se voir dans le fil et non arriver par un canal séparé dont l'autre
 * partie n'aurait pas connaissance.
 */
enum MessageAuthor: string
{
    case Traveller = 'traveller';
    case Owner = 'owner';
    case Vayla = 'vayla';

    public function label(): string
    {
        return match ($this) {
            self::Traveller => 'Voyageur',
            self::Owner => 'Propriétaire',
            self::Vayla => 'Vayla',
        };
    }

    /** L'autre partie : celle pour qui ce message est « non lu ». */
    public function destinataire(): self
    {
        return match ($this) {
            self::Traveller => self::Owner,
            self::Owner => self::Traveller,
            // Un mot de Vayla s'adresse aux deux : il compte comme non lu
            // pour le propriétaire, qui est celui qui doit agir.
            self::Vayla => self::Owner,
        };
    }
}
