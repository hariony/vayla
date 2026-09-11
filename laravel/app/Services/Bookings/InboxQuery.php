<?php

namespace App\Services\Bookings;

use App\Data\Bookings\InboxPageData;
use App\Models\Owner;
use App\Services\ConversationService;

/**
 * Les deux boîtes : **un message se rate, et c'est ce qu'elles réparent**. Elles
 * mènent au fil, elles ne le rendent pas — la conversation s'ouvre sur la
 * réservation, avec ses dates et ses boutons.
 */
final class InboxQuery
{
    public function __construct(
        private ConversationService $conversations,
    ) {}

    public function proprietaire(Owner $owner): InboxPageData
    {
        return new InboxPageData($this->conversations->boiteDuProprietaire($owner));
    }

    /** Côté voyageur, l'adresse du compte tient lieu d'identité : c'est elle qui rattache les séjours. */
    public function voyageur(string $email): InboxPageData
    {
        return new InboxPageData($this->conversations->boiteDuVoyageur($email));
    }
}
