<?php

namespace App\Services;

use App\Contracts\Bookings\BookingCancellation;
use App\Contracts\Repositories\BookingMessageRepositoryInterface;
use App\Contracts\Repositories\BookingRepositoryInterface;
use App\Contracts\Settings\SettingsStore;
use App\Data\DateRangeData;
use App\Data\SejourData;
use App\DTOs\Bookings\BookingTermsDto;
use App\DTOs\Bookings\NewBookingDto;
use App\Enums\BookingStatus;
use App\Enums\MessageAuthor;
use App\Exceptions\BookingRefusedException;
use App\Models\Booking;
use App\Models\Listing;
use App\Models\StayConfirmation;
use App\Services\Notifications\OwnerNotifier;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Les réservations : ce que Vayla garantit, et ce qu'elle ne garantit pas.
 *
 * **Aucun argent ne transite.** Une réservation met en relation et retire
 * des nuits du calendrier ; l'acompte se convient entre le voyageur et le
 * propriétaire, hors plateforme. C'est le choix de fond, et il tient à une
 * réalité : faire payer un acompte en ligne à Madagascar est un risque que
 * Vayla ne peut pas porter aujourd'hui.
 *
 * Deux règles protègent le propriétaire, et elles comptent autant que le
 * reste :
 *
 * 1. **Une demande sans réponse expire.** Elle bloque les dates dès la
 *    seconde où elle est déposée — sinon deux voyageurs réservent la même
 *    semaine — mais elle les rend au bout de `hold_hours`. Un propriétaire
 *    distrait ne doit pas voir son calendrier se fermer tout seul.
 * 2. **Le chevauchement est refusé en base, pas dans le formulaire.** Deux
 *    demandes simultanées sur les mêmes nuits passeraient toutes les deux
 *    une vérification faite avant l'écriture : le contrôle est donc dans la
 *    transaction, avec un verrou sur les lignes de l'annonce.
 *
 * Et la règle qui fait vivre l'entreprise : **on facture le séjour effectué,
 * jamais la réservation**. `complete()` ne s'appelle qu'avec une
 * confirmation de voyageur.
 */
class BookingService implements BookingCancellation
{
    public function __construct(
        private AvailabilityService $availability,
        private OwnerNotifier $notifier,
        private SettingsStore $reglages,
        private BookingRepositoryInterface $reservations,
        private BookingMessageRepositoryInterface $messages,
    ) {}

    /** @throws BookingRefusedException */
    public function book(Listing $listing, NewBookingDto $demande): Booking
    {
        $arrival = Carbon::parse($demande->arrival)->startOfDay();
        $departure = Carbon::parse($demande->departure)->startOfDay();
        $nights = (int) $arrival->diffInDays($departure);

        $this->guard($listing, $arrival, $departure, $nights, $demande->guests);

        $booking = DB::transaction(function () use ($listing, $demande, $arrival, $departure, $nights) {
            $this->reservations->verrouiller($listing);

            if ($this->overlaps($listing, $arrival, $departure)) {
                throw new BookingRefusedException('Ces nuits viennent d\'être prises.');
            }

            $booking = $this->reservations->creer($listing, $demande, $this->termes($listing, $nights));

            /*
             * Le mot déposé avec la demande **ouvre le fil d'échange**.
             * Le laisser dans sa seule colonne ferait deux endroits où vivent
             * les mots d'un voyageur — et l'écran finirait par n'en montrer
             * qu'un des deux, en général celui qui n'a pas la réponse.
             */
            if (trim((string) $demande->message) !== '') {
                $this->messages->ecrire($booking, MessageAuthor::Traveller, trim($demande->message));
            }

            return $booking;
        });

        /*
         * **Le message part après la transaction, jamais dedans.** Écrit à
         * l'intérieur, il annoncerait une demande qui n'existe pas le jour où
         * la transaction est annulée — et le propriétaire répondrait à un
         * séjour introuvable.
         *
         * Sans cette ligne, tout le reste ne sert à rien : une demande a
         * quarante-huit heures pour être répondue, et il faudrait que le
         * propriétaire pense à ouvrir Vayla dans cette fenêtre.
         */
        $this->notifier->nouvelleDemande($booking->load('listing.owner'));

        return $booking;
    }

    public function accept(Booking $booking): Booking
    {
        $this->assertOpen($booking);
        $this->reservations->accepter($booking);

        return $booking;
    }

    public function decline(Booking $booking, ?string $reason = null): Booking
    {
        $this->assertOpen($booking);
        $this->reservations->refuser($booking, $reason);

        return $booking;
    }

    public function cancel(Booking $booking, ?string $reason = null): Booking
    {
        if ($booking->status->isFinal()) {
            throw new BookingRefusedException('Cette réservation est déjà close.');
        }

        $this->reservations->annuler($booking, $reason);

        return $booking;
    }

    /**
     * Le séjour a eu lieu : c'est le voyageur qui le dit, et c'est ce qui
     * rend la nuit facturable. Sans confirmation, une réservation acceptée
     * reste une intention — et une intention ne se facture pas.
     */
    public function complete(Booking $booking, StayConfirmation $confirmation): Booking
    {
        if ($booking->status !== BookingStatus::Accepted) {
            throw new BookingRefusedException('Seule une réservation acceptée peut être confirmée.');
        }

        DB::transaction(fn () => $this->reservations->terminer($booking, $confirmation));

        return $booking;
    }

    /**
     * Rend au calendrier les nuits des demandes restées sans réponse.
     * Appelée par la commande planifiée, et idempotente.
     */
    public function releaseExpired(): int
    {
        return $this->reservations->expirerLesDemandes();
    }

    /**
     * **Le prix et le taux sont figés à la réservation** : le propriétaire peut
     * réévaluer son tarif, le taux peut monter — la ligne déjà engagée ne bouge
     * pas.
     */
    private function termes(Listing $listing, int $nights): BookingTermsDto
    {
        return new BookingTermsDto(
            reference: $this->reference(),
            nights: $nights,
            pricePerNight: (int) $listing->price,
            total: (int) $listing->price * $nights,
            commissionRate: $this->reglages->commission(),
            holdExpiresAt: Carbon::now()->addHours((int) config('vayla.booking.hold_hours')),
        );
    }

    private function assertOpen(Booking $booking): void
    {
        if ($booking->status !== BookingStatus::Pending) {
            throw new BookingRefusedException('Cette réservation n\'attend plus de réponse.');
        }
    }

    private function guard(Listing $listing, Carbon $arrival, Carbon $departure, int $nights, int $guests): void
    {
        if ($arrival->isBefore(Carbon::today())) {
            throw new BookingRefusedException('La date d\'arrivée est passée.');
        }

        if ($nights < 1) {
            throw new BookingRefusedException('Le départ doit être après l\'arrivée.');
        }

        if ($nights < $listing->min_nights) {
            throw new BookingRefusedException("Ce logement se loue à partir de {$listing->min_nights} nuits.");
        }

        if ($listing->max_nights && $nights > $listing->max_nights) {
            throw new BookingRefusedException("Ce logement se loue au plus {$listing->max_nights} nuits d'affilée.");
        }

        if ($guests > $listing->guests) {
            throw new BookingRefusedException("Ce logement accueille au plus {$listing->guests} voyageurs.");
        }

        if ($this->overlaps($listing, $arrival, $departure)) {
            throw new BookingRefusedException('Ces nuits ne sont pas libres.');
        }
    }

    /** Une seule nuit prise entre l'arrivée et la veille du départ suffit. */
    private function overlaps(Listing $listing, Carbon $arrival, Carbon $departure): bool
    {
        $sejour = SejourData::depuis($arrival->toDateString(), $departure->toDateString());

        if (! $sejour) {
            return false;
        }

        // La soustraction du jour de départ vit dans `SejourData`, une seule
        // fois : parcourir les nuits une par une ici en redonnait une
        // deuxième écriture, et deux écritures d'une même règle finissent
        // toujours par diverger.
        return collect($this->availability->blocked($listing->fresh(['unavailabilities', 'bookings'])))
            ->contains(fn (DateRangeData $p) => $sejour->couvre($p->from, $p->to));
    }

    /**
     * Un code court, sans caractères ambigus : il va être dicté au téléphone
     * et recopié sur WhatsApp. Ni O ni 0, ni I ni 1.
     */
    private function reference(): string
    {
        do {
            $code = 'VY-'.Str::upper(Str::password(5, symbols: false, numbers: true, letters: true));
            $code = strtr($code, ['O' => 'R', '0' => '4', 'I' => 'K', '1' => '7', 'L' => 'M']);
        } while ($this->reservations->referenceExiste($code));

        return $code;
    }
}
