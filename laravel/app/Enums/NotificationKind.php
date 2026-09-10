<?php

namespace App\Enums;

/**
 * Ce qu'un message WhatsApp annonce.
 *
 * **Quatre motifs, pas un de plus.** Chaque notification est un message qui
 * arrive sur le téléphone de quelqu'un : au-delà de ce qui exige vraiment une
 * action, on devient l'expéditeur qu'on met en sourdine — et le jour où une
 * demande expire, le message qui comptait est noyé avec les autres.
 */
enum NotificationKind: string
{
    case NouvelleDemande = 'nouvelle_demande';
    case DemandeExpireBientot = 'demande_expire_bientot';
    case NouveauMessage = 'nouveau_message';
    case LienAcces = 'lien_acces';
    case Test = 'test';

    public function label(): string
    {
        return match ($this) {
            self::NouvelleDemande => 'Nouvelle demande de séjour',
            self::DemandeExpireBientot => 'Demande sur le point d’expirer',
            self::NouveauMessage => 'Nouveau message d’un voyageur',
            self::LienAcces => 'Lien d’accès à l’espace propriétaire',
            self::Test => 'Test du canal',
        };
    }

    /**
     * Un motif urgent passe devant dans la file : celui qui envoie à la main
     * n'a pas le temps de trier, et une demande qui expire dans deux heures
     * ne peut pas attendre derrière dix liens d'accès.
     */
    public function urgent(): bool
    {
        return $this === self::DemandeExpireBientot || $this === self::NouvelleDemande;
    }
}
