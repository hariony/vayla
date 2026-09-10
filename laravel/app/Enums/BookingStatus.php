<?php

namespace App\Enums;

/**
 * Le cycle de vie d'une réservation.
 *
 * Six états, et deux d'entre eux existent uniquement pour protéger le
 * propriétaire :
 *
 * - `Pending` **bloque déjà les dates**, sinon deux voyageurs réservent la
 *   même semaine. Mais un blocage sans contrepartie est un vol de
 *   disponibilité : la demande **expire** si le propriétaire ne répond pas.
 * - `Expired` n'est donc pas un échec technique, c'est une garantie. Sans
 *   elle, un propriétaire distrait verrait son calendrier se fermer tout
 *   seul et quitterait la plateforme au bout d'un mois.
 *
 * `Completed` est le seul état facturable, et il ne s'atteint pas en
 * réservant : il s'atteint quand un voyageur **confirme y avoir dormi**.
 * Facturer sur la réservation reviendrait à facturer les no-shows — le
 * propriétaire refuserait de payer, et il aurait raison.
 */
enum BookingStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Declined = 'declined';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente du propriétaire',
            self::Accepted => 'Acceptée',
            self::Declined => 'Refusée',
            self::Expired => 'Expirée, sans réponse',
            self::Cancelled => 'Annulée',
            self::Completed => 'Séjour effectué',
        };
    }

    /**
     * Les états qui retirent des nuits du calendrier.
     *
     * `Completed` n'y est pas : le séjour est passé, ses nuits sont
     * derrière nous et n'ont plus à occuper les mois à venir.
     *
     * @return array<int, self>
     */
    public static function blocking(): array
    {
        return [self::Pending, self::Accepted];
    }

    public function blocksDates(): bool
    {
        return in_array($this, self::blocking(), true);
    }

    /** Seul un séjour confirmé par le voyageur se facture. */
    public function isBillable(): bool
    {
        return $this === self::Completed;
    }

    /** Un état terminal ne se rouvre pas. */
    public function isFinal(): bool
    {
        return in_array($this, [self::Declined, self::Expired, self::Cancelled, self::Completed], true);
    }
}
