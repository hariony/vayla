<?php

namespace App\Enums;

/**
 * Les onglets de la file des réservations, dans l'ordre. « Toutes » vient en
 * dernier : on ouvre la file pour ce qui attend, pas pour l'historique.
 */
enum BookingQueueFilter: string
{
    case Attente = 'attente';
    case Acceptees = 'acceptees';
    case Effectuees = 'effectuees';
    case Closes = 'closes';
    case Toutes = 'toutes';

    public function label(): string
    {
        return match ($this) {
            self::Attente => 'En attente',
            self::Acceptees => 'Acceptées',
            self::Effectuees => 'Séjours effectués',
            self::Closes => 'Closes',
            self::Toutes => 'Toutes',
        };
    }

    /** @return array<int, BookingStatus>|null `null` pour « Toutes » */
    public function statuts(): ?array
    {
        return match ($this) {
            self::Attente => [BookingStatus::Pending],
            self::Acceptees => [BookingStatus::Accepted],
            self::Effectuees => [BookingStatus::Completed],
            self::Closes => [BookingStatus::Declined, BookingStatus::Expired, BookingStatus::Cancelled],
            self::Toutes => null,
        };
    }
}
