<?php

namespace App\Services;

use App\Enums\MessageAuthor;
use App\Models\Booking;
use App\Models\BookingMessage;
use App\Models\Owner;
use App\Repositories\Contracts\BookingMessageRepositoryInterface;
use App\Services\Notifications\OwnerNotifier;

/**
 * L'échange écrit autour d'une réservation.
 *
 * **Ce que ce fil est, et ce qu'il n'est pas.** Vayla ne cache pas les numéros
 * de téléphone : tout le modèle est la mise en relation directe, et le
 * va-et-vient rapide se fera sur WhatsApp de toute façon — les deux parties y
 * sont déjà. Vouloir remplacer WhatsApp serait perdu d'avance.
 *
 * Ce que WhatsApp ne donne pas, c'est **une trace rattachée à un séjour**. Le
 * jour où un voyageur affirme qu'on lui avait promis la climatisation, ou
 * qu'un propriétaire dit avoir prévenu d'une coupure d'eau, il faut pouvoir
 * relire ce qui a été écrit et quand. C'est la seule raison d'être de ce fil,
 * et c'est pour ça qu'il est **attaché à une réservation** plutôt qu'ouvert à
 * tous : un « contacter le propriétaire » sans réservation serait une surface
 * de démarchage sans responsabilité.
 *
 * **Vayla peut lire, et c'est écrit à l'écran.** Une trace dont personne ne
 * sait qu'elle est lisible ne sert de médiation à personne.
 */
class ConversationService
{
    public function __construct(
        private BookingMessageRepositoryInterface $messages,
        private OwnerNotifier $notifier,
    ) {}

    /**
     * Le fil, prêt pour l'affichage.
     *
     * `moi` dit de quel côté chaque message se range : le même fil est rendu
     * par l'écran du propriétaire et par celui du voyageur, et c'est la seule
     * chose qui les distingue.
     *
     * @return array<int, array<string, mixed>>
     */
    public function fil(Booking $booking, MessageAuthor $lecteur): array
    {
        return $this->messages->fil($booking)
            ->map(fn (BookingMessage $m) => [
                'id' => $m->id,
                'author' => $m->author->value,
                'authorLabel' => $m->author->label(),
                'moi' => $m->author === $lecteur,
                'body' => $m->body,
                'at' => $m->created_at->toIso8601String(),
            ])
            ->all();
    }

    public function ecrire(Booking $booking, MessageAuthor $auteur, string $corps): BookingMessage
    {
        $message = $this->messages->ecrire($booking, $auteur, trim($corps));

        /*
         * **Seul le voyageur déclenche une notification.** Prévenir le
         * propriétaire de son propre message serait absurde, et le fil ne
         * notifie pas le voyageur : il n'a pas de compte, et son numéro sert
         * au propriétaire qui l'appelle — pas à Vayla qui lui écrirait.
         */
        if ($auteur === MessageAuthor::Traveller) {
            $this->notifier->nouveauMessage($booking->load('listing.owner'), $message->body);
        }

        return $message;
    }

    /**
     * Ouvrir le fil vaut lecture.
     *
     * Un bouton « marquer comme lu » séparé serait un geste de plus à
     * comprendre pour un public dont c'est le premier outil en ligne — et un
     * compteur qui ne redescend pas tout seul finit par être ignoré, donc par
     * ne plus rien signaler.
     */
    public function marquerLu(Booking $booking, MessageAuthor $lecteur): void
    {
        $this->messages->marquerLu($booking, $lecteur);
    }

    /** Le nombre de conversations qui attendent le propriétaire. */
    public function nonLus(Owner $owner): int
    {
        return $this->messages->nonLusPour($owner);
    }
}
