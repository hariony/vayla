<?php

namespace App\Repositories;

use App\Contracts\Repositories\BookingMessageRepositoryInterface;
use App\Enums\MessageAuthor;
use App\Models\Booking;
use App\Models\BookingMessage;
use App\Models\Owner;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class BookingMessageRepository implements BookingMessageRepositoryInterface
{
    public function fil(Booking $booking): Collection
    {
        return $booking->messages()->get();
    }

    public function ecrire(Booking $booking, MessageAuthor $auteur, string $corps): BookingMessage
    {
        $message = BookingMessage::create([
            'booking_id' => $booking->id,
            'author' => $auteur,
            'body' => $corps,
        ]);

        // Écrire, c'est avoir lu : sans ça, son propre message compterait
        // comme non lu pour lui-même à la prochaine ouverture.
        $this->marquerLu($booking, $auteur);

        return $message;
    }

    public function marquerLu(Booking $booking, MessageAuthor $partie): void
    {
        $booking->forceFill([$this->colonne($partie) => Carbon::now()])->save();
    }

    /**
     * Le compte affiché sur l'onglet « Réservations ».
     *
     * On compte les **réservations**, pas les messages : « 2 » à côté d'un
     * onglet veut dire « deux conversations vous attendent », pas « quatorze
     * lignes de texte ». Un compte de messages ferait paniquer pour un
     * voyageur bavard.
     */
    public function nonLusPour(Owner $owner): int
    {
        return $this->duProprietaire($owner)->whereHas('messages', $this->neufsPour(MessageAuthor::Owner))->count();
    }

    public function nonLusPourAdresse(string $email): int
    {
        return $this->deLAdresse($email)->whereHas('messages', $this->neufsPour(MessageAuthor::Traveller))->count();
    }

    public function conversationsDuProprietaire(Owner $owner): Collection
    {
        return $this->listees($this->duProprietaire($owner));
    }

    public function conversationsDeLAdresse(string $email): Collection
    {
        return $this->listees($this->deLAdresse($email));
    }

    /**
     * Les réservations d'un propriétaire.
     *
     * Par les identifiants de ses logements, jamais par une jointure ouverte :
     * c'est **la** portée de sécurité de l'espace, et elle doit rester à un
     * seul endroit.
     *
     * @return Builder<Booking>
     */
    private function duProprietaire(Owner $owner): Builder
    {
        return Booking::query()->whereIn('listing_id', $owner->listings->pluck('id'));
    }

    /**
     * Les réservations d'un voyageur.
     *
     * L'adresse tient lieu d'identité : le voyageur n'a pas besoin d'un compte
     * pour réserver, et c'est elle qui rattache ses séjours le jour où il en
     * ouvre un.
     *
     * @return Builder<Booking>
     */
    private function deLAdresse(string $email): Builder
    {
        return Booking::query()->where('traveller_email', $email);
    }

    /**
     * Les messages **neufs pour cette partie** : écrits par quelqu'un d'autre,
     * et postérieurs à sa dernière ouverture du fil.
     *
     * Un mot de Vayla compte comme neuf pour les deux : c'est une médiation,
     * elle doit être vue de part et d'autre.
     */
    private function neufsPour(MessageAuthor $lecteur): callable
    {
        $colonne = 'bookings.'.$this->colonne($lecteur);

        return function ($q) use ($lecteur, $colonne) {
            $q->where('author', '!=', $lecteur->value)
                // La condition tient dans **une seule clause** : un `orWhere`
                // au premier niveau aurait, à la première retouche, ramené les
                // réservations de tout le monde.
                ->where(fn ($neuf) => $neuf->whereNull($colonne)
                    ->orWhereColumn('booking_messages.created_at', '>', $colonne));
        };
    }

    /**
     * La liste d'une boîte : seules les réservations qui portent un fil, la
     * plus récemment écrite d'abord.
     *
     * Le tri se fait sur le **dernier message**, pas sur la réservation : une
     * conversation qui reprend six mois après doit remonter, sinon la boîte
     * classe par date d'arrivée et enterre ce qui vient d'arriver.
     *
     * @param  Builder<Booking>  $requete
     * @return Collection<int, Booking>
     */
    private function listees(Builder $requete): Collection
    {
        return $requete
            ->has('messages')
            ->with(['messages', 'listing.destination'])
            ->withMax('messages', 'created_at')
            ->orderByDesc('messages_max_created_at')
            ->get();
    }

    private function colonne(MessageAuthor $partie): string
    {
        return match ($partie) {
            MessageAuthor::Owner, MessageAuthor::Vayla => 'owner_read_at',
            MessageAuthor::Traveller => 'traveller_read_at',
        };
    }
}
