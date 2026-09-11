<?php

namespace App\Enums;

/**
 * L'issue d'une demande, telle que les statistiques la comptent. Un séjour
 * effectué est une demande **acceptée** : son issue ne change pas parce que le
 * voyageur est venu.
 */
enum BookingOutcome: string
{
    case Acceptees = 'acceptees';
    case Attente = 'attente';
    case Refusees = 'refusees';
    case Expirees = 'expirees';
    case Annulees = 'annulees';

    public static function de(BookingStatus $statut): self
    {
        return match ($statut) {
            BookingStatus::Accepted, BookingStatus::Completed => self::Acceptees,
            BookingStatus::Pending => self::Attente,
            BookingStatus::Declined => self::Refusees,
            BookingStatus::Expired => self::Expirees,
            BookingStatus::Cancelled => self::Annulees,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Acceptees => 'Acceptées',
            self::Attente => 'En attente',
            self::Refusees => 'Refusées',
            self::Expirees => 'Expirées sans réponse',
            self::Annulees => 'Annulées',
        };
    }

    public function teinte(): string
    {
        return match ($this) {
            self::Acceptees => 'encre',
            self::Attente => 'terre',
            self::Refusees => 'gris',
            self::Expirees => 'terre-pale',
            self::Annulees => 'filet',
        };
    }

    /**
     * Le propriétaire a-t-il eu à trancher ? **Une demande en attente n'a pas
     * encore échoué**, et une demande annulée par le voyageur ne lui a rien
     * demandé : ni l'une ni l'autre ne compte dans le taux de réponse.
     */
    public function tranchee(): bool
    {
        return ! in_array($this, [self::Attente, self::Annulees], true);
    }
}
