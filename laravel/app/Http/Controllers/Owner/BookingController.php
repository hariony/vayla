<?php

namespace App\Http\Controllers\Owner;

use App\Enums\BookingStatus;
use App\Enums\MessageAuthor;
use App\Http\Controllers\Controller;
use App\Http\Requests\MessageRequest;
use App\Models\Booking;
use App\Models\Owner;
use App\Services\ConversationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Toutes les réservations d'un propriétaire, passées comprises.
 *
 * Le tableau de bord ne montre que ce qui exige une action — les demandes à
 * répondre, les séjours à venir. Cet écran-là répond à l'autre question, celle
 * qu'on se pose une fois par mois : « qui est venu, qu'est-ce qui a été
 * refusé, qu'est-ce qui a expiré ». Sans lui, une réservation répondue
 * disparaissait de l'espace et le propriétaire n'avait plus aucune trace.
 *
 * **Le filtre est une liste de pastilles, pas un menu déroulant.** Quatre
 * états tiennent à l'écran, et un choix visible se corrige sans rouvrir quoi
 * que ce soit.
 */
class BookingController extends Controller
{
    public function __construct(
        private ConversationService $conversations,
    ) {}

    public function index(Request $request): Response
    {
        $owner = $request->user('proprietaire');
        $filtre = $request->string('statut')->value() ?: 'tous';

        $lignes = $owner->bookings
            ->sortByDesc('arrival')
            ->when($filtre !== 'tous', fn ($c) => $c->filter(
                fn (Booking $b) => $b->status->value === $filtre
            ));

        return Inertia::render('Owner/Bookings/Index', [
            'bookings' => $lignes->map(fn (Booking $b) => $this->ligne($b, $owner))->values()->all(),
            'filtre' => $filtre,
            'compteurs' => $this->compteurs($owner),
        ]);
    }

    /**
     * Le détail d'une réservation, avec son fil d'échange.
     *
     * L'ouvrir vaut lecture : un bouton « marquer comme lu » séparé serait un
     * geste de plus à comprendre, et un compteur qui ne redescend pas tout
     * seul finit par être ignoré — donc par ne plus rien signaler.
     */
    public function show(Request $request, string $reference): Response
    {
        $owner = $request->user('proprietaire');
        $booking = $this->sienne($owner, $reference);

        $this->conversations->marquerLu($booking, MessageAuthor::Owner);

        return Inertia::render('Owner/Bookings/Show', [
            'booking' => $this->ligne($booking, $owner) + [
                'listingSlug' => $owner->listings->firstWhere('id', $booking->listing_id)?->slug,
            ],
            'messages' => $this->conversations->fil($booking, MessageAuthor::Owner),
        ]);
    }

    public function reply(MessageRequest $request, string $reference): RedirectResponse
    {
        $booking = $this->sienne($request->user('proprietaire'), $reference);

        $this->conversations->ecrire($booking, MessageAuthor::Owner, $request->string('body')->value());

        return back()->with('succes', 'Message envoyé au voyageur.');
    }

    /**
     * La session dit qui l'on est, pas ce qu'on a le droit de lire : les
     * références sont courtes et se dictent au téléphone, et sans ce contrôle
     * une session valide plus une référence devinée ouvrirait la conversation
     * d'un confrère.
     */
    private function sienne(Owner $owner, string $reference): Booking
    {
        return Booking::query()
            ->where('reference', $reference)
            ->whereIn('listing_id', $owner->listings->pluck('id'))
            ->firstOr(fn () => abort(404));
    }

    /** @return array<string, mixed> */
    private function ligne(Booking $b, Owner $owner): array
    {
        return [
            'reference' => $b->reference,
            'listing' => $owner->listings->firstWhere('id', $b->listing_id)?->title ?? '—',
            'traveller' => $b->traveller,
            'phone' => $b->traveller_phone,
            'guests' => $b->guests,
            'arrival' => $b->arrival->toDateString(),
            'departure' => $b->departure->toDateString(),
            'nights' => $b->nights,
            'total' => $b->total,
            'commission' => $b->commission(),
            'status' => $b->status->value,
            'statusLabel' => $b->status->label(),
            // Seul un séjour confirmé par le voyageur se facture : le dire ici
            // évite la question « pourquoi cette ligne n'est pas sur ma facture ».
            'facturable' => $b->isBillable(),
            'reason' => $b->closed_reason,
            'messages' => $b->messages->count(),
            // Un fil jamais ouvert compte comme entièrement neuf.
            'nonLus' => $b->messages
                ->where('author', '!=', MessageAuthor::Owner)
                ->filter(fn ($m) => $b->owner_read_at === null || $m->created_at->greaterThan($b->owner_read_at))
                ->count(),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function compteurs(Owner $owner): array
    {
        $tous = ['value' => 'tous', 'label' => 'Toutes', 'n' => $owner->bookings->count()];

        $etats = collect(BookingStatus::cases())
            ->map(fn (BookingStatus $s) => [
                'value' => $s->value,
                'label' => $s->label(),
                'n' => $owner->bookings->where('status', $s)->count(),
            ])
            // Un filtre qui ne ramènerait rien n'a pas à occuper une pastille.
            ->filter(fn (array $e) => $e['n'] > 0)
            ->values()
            ->all();

        return [$tous, ...$etats];
    }
}
