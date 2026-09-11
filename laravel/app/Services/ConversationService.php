<?php

namespace App\Services;

use App\Contracts\Bookings\BookingThread;
use App\Contracts\Repositories\BookingMessageRepositoryInterface;
use App\Data\Bookings\ConversationData;
use App\Data\Bookings\MessageData;
use App\Enums\MessageAuthor;
use App\Models\Booking;
use App\Models\BookingMessage;
use App\Models\Owner;
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
class ConversationService implements BookingThread
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
     * @return list<MessageData>
     */
    public function fil(Booking $booking, MessageAuthor $lecteur): array
    {
        return $this->messages->fil($booking)
            ->map(fn (BookingMessage $m) => MessageData::fromModel($m, $lecteur))
            ->values()
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

    /** Idem côté voyageur, où l'adresse du compte tient lieu d'identité. */
    public function nonLusVoyageur(string $email): int
    {
        return $this->messages->nonLusPourAdresse($email);
    }

    /**
     * La boîte du propriétaire : une ligne par réservation qui porte un fil.
     *
     * @return list<ConversationData>
     */
    public function boiteDuProprietaire(Owner $owner): array
    {
        return $this->messages->conversationsDuProprietaire($owner)
            ->map(fn (Booking $b) => $this->ligne($b, MessageAuthor::Owner, $b->traveller))
            ->all();
    }

    /**
     * La boîte du voyageur.
     *
     * **Le correspondant n'y est pas nommé, le logement l'est.** Le voyageur a
     * écrit à propos d'une maison, pas à une personne dont il ne connaît pas
     * encore le nom au moment de la demande — et le propriétaire n'a pas à
     * apparaître dans une liste avant d'avoir accepté.
     *
     * @return list<ConversationData>
     */
    public function boiteDuVoyageur(string $email): array
    {
        return $this->messages->conversationsDeLAdresse($email)
            ->map(fn (Booking $b) => $this->ligne($b, MessageAuthor::Traveller, $b->listing?->title))
            ->all();
    }

    /**
     * Une ligne de boîte.
     *
     * **L'extrait est celui du dernier message, quel qu'en soit l'auteur.**
     * Ne montrer que le dernier message reçu ferait disparaître sa propre
     * réponse : on ne saurait plus si on a répondu, ce qui est justement la
     * question qu'on se pose en ouvrant une boîte.
     */
    private function ligne(Booking $booking, MessageAuthor $lecteur, ?string $sujet): ConversationData
    {
        $dernier = $booking->messages->last();
        $lu = $lecteur === MessageAuthor::Traveller
            ? $booking->traveller_read_at
            : $booking->owner_read_at;

        return new ConversationData(
            reference: $booking->reference,
            sujet: $sujet,
            listing: $booking->listing?->title,
            place: $booking->listing?->destination?->name,
            arrival: $booking->arrival->toDateString(),
            departure: $booking->departure->toDateString(),
            statut: $booking->status->value,
            statutLabel: $booking->status->label(),
            auteur: $dernier?->author->value,
            auteurLabel: $dernier?->author->label(),
            extrait: $dernier ? $this->extrait($dernier->body) : null,
            quand: $dernier?->created_at->toIso8601String(),
            // Non lu : le dernier mot vient de l'autre partie, et il est
            // postérieur à la dernière ouverture du fil.
            nonLu: $dernier !== null
                && $dernier->author !== $lecteur
                && ($lu === null || $dernier->created_at->greaterThan($lu)),
            messages: $booking->messages->count(),
        );
    }

    /**
     * **Une ligne, pas trois.** Une boîte se parcourt du regard : un extrait
     * qui déborde fait scruter le mauvais message. La coupe tombe sur un
     * espace pour ne pas trancher un mot en deux.
     */
    private function extrait(string $corps): string
    {
        $plat = trim(preg_replace('/\s+/', ' ', $corps) ?? '');

        return mb_strlen($plat) <= 120 ? $plat : rtrim(mb_substr($plat, 0, 117)).'…';
    }
}
