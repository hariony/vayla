<?php

namespace App\Enums;

/**
 * Où en est une demande de séjour : **qui s'en occupe se voit**. Deux membres
 * de l'équipe qui écrivent au même voyageur sans le savoir, c'est un voyageur
 * qui reçoit deux fois la même question.
 */
enum StayRequestStatus: string
{
    case New = 'nouvelle';
    case Taken = 'en_cours';
    case Closed = 'close';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Nouvelle',
            self::Taken => 'En cours',
            self::Closed => 'Close',
        };
    }
}
