<?php

namespace App\Services\Office;

use App\Enums\AdminActionKind;
use App\Enums\BookingStatus;
use App\Enums\ListingStatus;
use App\Enums\MessageAuthor;
use App\Enums\TrustLevel;
use App\Models\Admin;
use App\Models\AdminAction;
use App\Models\Amenity;
use App\Models\Booking;
use App\Models\InvoiceSettlement;
use App\Models\Listing;
use App\Models\OutboundMessage;
use App\Models\Owner;
use App\Models\Photo;
use App\Models\User;
use App\Repositories\Contracts\OfficeRepositoryInterface;
use App\Repositories\OfficeRepository;
use App\Services\ConversationService;
use App\Services\InvoiceService;
use App\Services\Settings\SettingsService;
use App\Support\Telephone;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

/**
 * Ce que les écrans du back-office reçoivent.
 *
 * **Tout est mis à plat ici, rien n'arrive au front en modèle.** C'est la
 * leçon de `auth.user`, qui publiait le compte entier dans le `data-page` de
 * chaque écran : un modèle sérialisé emporte ce qu'on n'a pas pensé à cacher.
 * Le back-office voit plus que le site — adresses exactes, numéros, courriels —
 * et c'est précisément pourquoi chaque champ y est nommé.
 */
class OfficeReadService
{
    /** Au-delà, une demande qui attend est à relancer par téléphone. */
    public const HEURES_URGENCE = 12;

    public function __construct(
        private OfficeRepositoryInterface $depot,
        private ModerationService $moderation,
        private AdminJournal $journal,
        private ConversationService $conversations,
        private InvoiceService $factures,
    ) {}

    // ── Tableau de bord ─────────────────────────────────────────────

    /**
     * **Ce qui attend quelqu'un, avant ce qui se mesure.** On n'ouvre pas le
     * back-office pour regarder des courbes : on l'ouvre parce qu'une annonce
     * attend son appel, qu'une demande expire ou qu'un message WhatsApp n'est
     * pas parti. Les chiffres viennent après, et ne sont que des comptes —
     * aucune moyenne, aucune « tendance » qui se lirait comme une promesse.
     *
     * @return array<string, mixed>
     */
    public function tableau(): array
    {
        $annonces = $this->depot->comptesAnnonces();
        $reservations = $this->depot->comptesReservations();
        $aVerifier = $this->depot->annoncesAVerifier(5);
        $urgentes = $this->depot->demandesUrgentes(self::HEURES_URGENCE, 5);
        $whatsapp = OutboundMessage::query()->whereNull('sent_at')->count();
        $numeros = Owner::query()
            ->whereNull('phone_verified_at')
            ->whereHas('listings', fn ($q) => $q->where('status', ListingStatus::Submitted->value))
            ->count();

        $echelle = $this->depot->echelleEnLigne();
        $moisDernier = Carbon::today()->subMonthNoOverflow()->startOfMonth();
        $impayees = collect($this->lignesFacturation($moisDernier))->filter(fn ($l) => ! $l['settlement'])->count();

        return [
            'aTraiter' => [
                [
                    'cle' => 'annonces', 'nombre' => $annonces[ListingStatus::Submitted->value],
                    'label' => 'Annonces à vérifier', 'href' => '/annonces?statut=submitted',
                    'detail' => $aVerifier->first() ? 'La plus ancienne attend depuis '.$aVerifier->first()->updated_at->diffForHumans(null, true).'.' : 'Aucune fiche en attente.',
                ],
                [
                    'cle' => 'demandes',
                    'nombre' => Booking::query()
                        ->where('status', BookingStatus::Pending->value)
                        ->whereBetween('hold_expires_at', [Carbon::now(), Carbon::now()->addHours(self::HEURES_URGENCE)])
                        ->count(),
                    'label' => 'Demandes qui expirent sous '.self::HEURES_URGENCE.' h', 'href' => '/reservations?filtre=attente',
                    'detail' => $reservations['attente'].' demande'.($reservations['attente'] > 1 ? 's' : '').' en attente au total.',
                ],
                [
                    'cle' => 'whatsapp', 'nombre' => $whatsapp,
                    'label' => 'Messages WhatsApp à envoyer', 'href' => '/whatsapp',
                    'detail' => 'Un clic ouvre WhatsApp, le message est déjà écrit.',
                ],
                [
                    'cle' => 'numeros', 'nombre' => $numeros,
                    'label' => 'Numéros à vérifier par appel', 'href' => '/proprietaires?filtre=a-verifier',
                    'detail' => 'Propriétaires dont une fiche attend, numéro pas encore confirmé.',
                ],
                [
                    'cle' => 'factures', 'nombre' => $impayees,
                    'label' => 'Factures de '.$this->mois($moisDernier).' non réglées', 'href' => '/facturation?mois='.$moisDernier->format('Y-m'),
                    'detail' => 'Réglées par mobile money : consignez chaque règlement reçu.',
                ],
            ],
            'annonces' => $aVerifier->map(fn (Listing $l) => $this->ligneAnnonce($l))->all(),
            'demandes' => $urgentes->map(fn (Booking $b) => $this->ligneReservation($b))->all(),
            'chiffres' => [
                'enLigne' => $annonces[ListingStatus::Published->value],
                'proprietaires' => Owner::query()->count(),
                'voyageurs' => User::query()->count(),
                'reservationsMois' => Booking::query()->where('created_at', '>=', Carbon::today()->startOfMonth())->count(),
                'sejoursMois' => Booking::query()
                    ->where('status', BookingStatus::Completed->value)
                    ->where('departure', '>=', Carbon::today()->startOfMonth()->toDateString())
                    ->count(),
                'commissionEncours' => (int) collect($this->lignesFacturation(Carbon::today()->startOfMonth()))->sum('due'),
            ],
            'echelle' => collect(TrustLevel::cases())->map(fn (TrustLevel $n) => [
                'niveau' => $n->value,
                'label' => $n->label(),
                'nombre' => $echelle[$n->value],
            ])->all(),
            'activite' => AdminAction::query()->latest('id')->limit(8)->get()
                ->map(fn (AdminAction $a) => AdminJournal::ligne($a))->all(),
        ];
    }

    // ── Annonces ────────────────────────────────────────────────────

    /** @return array<string, mixed> */
    public function annonces(?ListingStatus $statut, ?string $recherche): array
    {
        $comptes = $this->depot->comptesAnnonces();

        return [
            'annonces' => $this->page($this->depot->annonces($statut, $recherche), fn (Listing $l) => $this->ligneAnnonce($l)),
            'onglets' => collect([ListingStatus::Submitted, ListingStatus::Published, ListingStatus::Draft, ListingStatus::Archived])
                ->map(fn (ListingStatus $s) => ['cle' => $s->value, 'label' => $this->libelleStatut($s), 'nombre' => $comptes[$s->value]])
                ->prepend(['cle' => 'toutes', 'label' => 'Toutes', 'nombre' => array_sum($comptes)])
                ->all(),
            'filtre' => ['statut' => $statut?->value ?? 'toutes', 'q' => $recherche ?? ''],
        ];
    }

    /** @return array<string, mixed> */
    public function annonce(Listing $listing): array
    {
        $listing->loadMissing(['destination', 'owner', 'photos', 'amenities'])->loadCount('confirmations');

        $aVenir = $listing->bookings()
            ->whereIn('status', [BookingStatus::Pending->value, BookingStatus::Accepted->value])
            ->where('departure', '>=', Carbon::today()->toDateString())
            ->count();

        return [
            'annonce' => [
                ...$this->ligneAnnonce($listing),
                'kind' => $listing->kind?->label(),
                'summary' => $listing->summary,
                'description' => $listing->description,
                'capacite' => [
                    'guests' => $listing->guests, 'bedrooms' => $listing->bedrooms, 'beds' => $listing->beds,
                    'bathrooms' => $listing->bathrooms, 'surface' => $listing->surface,
                ],
                'sejour' => [
                    'min' => $listing->min_nights, 'max' => $listing->max_nights,
                    'checkIn' => $listing->check_in_from, 'checkOut' => $listing->check_out_before,
                    'pets' => (bool) $listing->pets_allowed, 'smoking' => (bool) $listing->smoking_allowed, 'events' => (bool) $listing->events_allowed,
                ],
                'photos' => $listing->photos->map(fn (Photo $p) => $this->photo($p))->all(),
                'equipements' => $listing->amenities
                    ->sortBy(fn (Amenity $a) => [$a->group->position(), $a->position])
                    ->groupBy(fn (Amenity $a) => $a->group->label())
                    ->map(fn ($groupe, $label) => [
                        'label' => $label,
                        'items' => $groupe->map(fn (Amenity $a) => ['label' => $a->label, 'highlight' => (bool) $a->pivot->highlight, 'note' => $a->pivot->note])->values()->all(),
                    ])->values()->all(),
                'reviewNote' => $listing->review_note,
                'confirmations' => $listing->confirmations_count,
                'aVenir' => $aVenir,
            ],
            'proprietaire' => $listing->owner ? $this->carteProprietaire($listing->owner) : null,
            'niveaux' => collect(TrustLevel::cases())->map(fn (TrustLevel $n) => [
                'niveau' => $n->value,
                'label' => $n->label(),
                'summary' => $n->summary(),
                'actuel' => $n === $listing->trust_level,
                'raison' => $n === $listing->trust_level ? null : $this->moderation->pourquoiPasNiveau($listing, $n),
            ])->all(),
            'publication' => [
                'possible' => $this->moderation->pourquoiPasPublier($listing) === null,
                'raison' => $this->moderation->pourquoiPasPublier($listing),
                'renvoyable' => $listing->status === ListingStatus::Submitted,
                'archivable' => $listing->status !== ListingStatus::Archived,
            ],
            'journal' => $this->journal->pour($listing),
        ];
    }

    // ── Réservations ────────────────────────────────────────────────

    /** @return array<string, mixed> */
    public function reservations(string $filtre, ?string $recherche): array
    {
        $comptes = $this->depot->comptesReservations();
        $libelles = ['attente' => 'En attente', 'acceptees' => 'Acceptées', 'effectuees' => 'Séjours effectués', 'closes' => 'Closes'];

        return [
            'reservations' => $this->page($this->depot->reservations($filtre, $recherche), fn (Booking $b) => $this->ligneReservation($b)),
            'onglets' => collect(array_keys(OfficeRepository::FILTRES_RESERVATIONS))
                ->map(fn (string $cle) => ['cle' => $cle, 'label' => $libelles[$cle], 'nombre' => $comptes[$cle]])
                ->push(['cle' => 'toutes', 'label' => 'Toutes', 'nombre' => array_sum($comptes)])
                ->all(),
            'filtre' => ['filtre' => $filtre, 'q' => $recherche ?? ''],
        ];
    }

    /** @return array<string, mixed> */
    public function reservation(Booking $booking): array
    {
        $booking->loadMissing(['listing.owner', 'listing.destination']);

        return [
            'reservation' => [
                ...$this->ligneReservation($booking),
                'message' => $booking->message,
                'pricePerNight' => $booking->price_per_night,
                'rate' => (float) $booking->commission_rate,
                'commission' => $booking->commission(),
                'answeredAt' => $booking->answered_at?->toIso8601String(),
                'completedAt' => $booking->completed_at?->toIso8601String(),
                'createdAt' => $booking->created_at->toIso8601String(),
                'closedReason' => $booking->closed_reason,
                'annulable' => ! $booking->status->isFinal(),
                'voyageur' => [
                    'name' => $booking->traveller,
                    'email' => $booking->traveller_email,
                    'telephone' => $this->telephone($booking->traveller_phone),
                ],
            ],
            'proprietaire' => $booking->listing?->owner ? $this->carteProprietaire($booking->listing->owner) : null,
            // Vayla lit sans marquer comme lu : ouvrir le fil depuis le
            // back-office ne doit pas faire croire au propriétaire qu'il a
            // répondu à tout.
            'messages' => $this->conversations->fil($booking, MessageAuthor::Vayla),
            'journal' => $this->journal->pour($booking),
        ];
    }

    // ── Propriétaires ───────────────────────────────────────────────

    /** @return array<string, mixed> */
    public function proprietaires(?string $filtre, ?string $recherche): array
    {
        return [
            'proprietaires' => $this->page($this->depot->proprietaires($filtre, $recherche), fn (Owner $o) => [
                ...$this->carteProprietaire($o),
                'listings' => $o->listings_count,
                'enLigne' => $o->en_ligne_count,
                'aVerifier' => $o->a_verifier_count,
                'bookings' => $o->bookings_count,
            ]),
            'onglets' => [
                ['cle' => 'tous', 'label' => 'Tous', 'nombre' => Owner::query()->count()],
                ['cle' => 'a-verifier', 'label' => 'Numéro à vérifier', 'nombre' => Owner::query()->whereNull('phone_verified_at')->count()],
            ],
            'filtre' => ['filtre' => $filtre ?? 'tous', 'q' => $recherche ?? ''],
        ];
    }

    /** @return array<string, mixed> */
    public function proprietaire(Owner $owner): array
    {
        $owner->load(['listings' => fn ($q) => $q->with(['destination', 'photos'])->latest('updated_at')]);

        $reglements = InvoiceSettlement::query()->where('owner_id', $owner->id)->get()
            ->keyBy(fn (InvoiceSettlement $s) => substr((string) $s->month, 0, 7));

        $historique = $this->factures->historique($owner, 12);

        return [
            'proprietaire' => [
                ...$this->carteProprietaire($owner),
                'address' => $owner->address,
                'mobileMoney' => $owner->mobile_money,
                'operator' => $owner->mobile_money_operator,
                'emailVerified' => $owner->email_verified_at !== null,
                'createdAt' => $owner->created_at?->toIso8601String(),
                'lastLoginAt' => $owner->last_login_at?->toIso8601String(),
                'accessKeySetAt' => $owner->access_key_set_at?->toIso8601String(),
            ],
            'annonces' => $owner->listings->map(fn (Listing $l) => $this->ligneAnnonce($l, avecProprietaire: false))->all(),
            'reservations' => $owner->bookings()->with('listing')->latest('arrival')->limit(12)->get()
                ->map(fn (Booking $b) => $this->ligneReservation($b))->all(),
            'factures' => [
                'encours' => $this->resumeFacture($historique['encours'], null),
                'passees' => collect($historique['factures'])
                    ->map(fn (array $f) => $this->resumeFacture($f, $reglements[substr($f['period']['from'], 0, 7)] ?? null))
                    ->all(),
            ],
            'journal' => $this->journal->pour($owner),
        ];
    }

    // ── Voyageurs ───────────────────────────────────────────────────

    /** @return array<string, mixed> */
    public function voyageurs(?string $recherche): array
    {
        return [
            'voyageurs' => $this->page($this->depot->voyageurs($recherche), fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'telephone' => $this->telephone($u->phone),
                'bookings' => (int) $u->bookings_count,
                'createdAt' => $u->created_at?->toIso8601String(),
            ]),
            'total' => User::query()->count(),
            'filtre' => ['q' => $recherche ?? ''],
        ];
    }

    // ── WhatsApp ────────────────────────────────────────────────────

    /** @return array<string, mixed> */
    public function whatsapp(bool $envoyes): array
    {
        return [
            'messages' => $this->page($this->depot->messagesWhatsApp($envoyes), fn (OutboundMessage $m) => [
                'id' => $m->id,
                'kind' => $m->kind->label(),
                'urgent' => $m->kind->urgent(),
                'to' => Telephone::depuis($m->to)?->lisible() ?? $m->to,
                'owner' => $m->owner ? ['id' => $m->owner->id, 'name' => $m->owner->name] : null,
                'booking' => $m->booking ? ['reference' => $m->booking->reference] : null,
                'body' => $m->body,
                'lien' => $m->lienWhatsApp(),
                'createdAt' => $m->created_at->toIso8601String(),
                'sentAt' => $m->sent_at?->toIso8601String(),
            ]),
            'onglets' => [
                ['cle' => 'a-envoyer', 'label' => 'À envoyer', 'nombre' => OutboundMessage::query()->whereNull('sent_at')->count()],
                ['cle' => 'envoyes', 'label' => 'Envoyés', 'nombre' => OutboundMessage::query()->whereNotNull('sent_at')->count()],
            ],
            'filtre' => ['onglet' => $envoyes ? 'envoyes' : 'a-envoyer'],
        ];
    }

    // ── Facturation ─────────────────────────────────────────────────

    /** @return array<string, mixed> */
    public function facturation(Carbon $mois): array
    {
        $debut = $mois->copy()->startOfMonth();
        $courant = Carbon::today()->startOfMonth();
        $lignes = $this->lignesFacturation($debut);

        $du = (int) collect($lignes)->sum('due');
        $regle = (int) collect($lignes)->filter(fn ($l) => $l['settlement'])->sum('due');

        return [
            'mois' => [
                'cle' => $debut->format('Y-m'),
                'label' => $this->mois($debut),
                'enCours' => $debut->equalTo($courant),
                'precedent' => $debut->copy()->subMonthNoOverflow()->format('Y-m'),
                'suivant' => $debut->lt($courant) ? $debut->copy()->addMonthNoOverflow()->format('Y-m') : null,
            ],
            'lignes' => $lignes,
            'totaux' => [
                'du' => $du,
                'regle' => $regle,
                'reste' => $du - $regle,
                'sejours' => (int) collect($lignes)->sum('stays'),
                'revenus' => (int) collect($lignes)->sum('revenue'),
            ],
            'taux' => app(SettingsService::class)->commission(),
        ];
    }

    // ── Journal et équipe ───────────────────────────────────────────

    /** @return array<string, mixed> */
    public function journal(?string $famille): array
    {
        $familles = collect(AdminActionKind::cases())->map->famille()->unique()->values();
        $libelles = [
            'annonces' => 'Annonces', 'proprietaires' => 'Propriétaires', 'reservations' => 'Réservations',
            'whatsapp' => 'WhatsApp', 'facturation' => 'Facturation', 'contenu' => 'Contenu', 'equipe' => 'Équipe',
        ];

        return [
            'lignes' => $this->page($this->depot->journal($famille), fn (AdminAction $a) => AdminJournal::ligne($a)),
            'onglets' => $familles->map(fn (string $f) => ['cle' => $f, 'label' => $libelles[$f] ?? $f])
                ->prepend(['cle' => 'tout', 'label' => 'Tout'])->all(),
            'filtre' => ['famille' => $famille ?? 'tout'],
        ];
    }

    /** @return array<string, mixed> */
    public function equipe(Admin $moi): array
    {
        return [
            'membres' => Admin::query()->orderBy('name')->get()->map(fn (Admin $a) => [
                'id' => $a->id,
                'name' => $a->name,
                'email' => $a->email,
                'initiales' => $a->initiales(),
                'moi' => $a->is($moi),
                'provisoire' => ! $a->motDePasseChoisi(),
                'lastLoginAt' => $a->last_login_at?->toIso8601String(),
                'actions' => $a->actions()->count(),
            ])->all(),
        ];
    }

    // ── Pièces communes ─────────────────────────────────────────────

    /**
     * Les factures d'un mois, avec leur règlement. Un propriétaire sans séjour
     * confirmé n'apparaît pas : une facture à zéro n'existe pas.
     *
     * @return array<int, array<string, mixed>>
     */
    private function lignesFacturation(Carbon $debut): array
    {
        $reglements = InvoiceSettlement::query()->where('month', $debut->toDateString())->get()->keyBy('owner_id');

        return Owner::query()->orderBy('name')->get()
            ->map(function (Owner $o) use ($debut, $reglements) {
                $facture = $this->factures->forOwner($o, $debut);

                return $facture['stays'] > 0
                    ? ['ownerId' => $o->id, ...$this->resumeFacture($facture, $reglements[$o->id] ?? null)]
                    : null;
            })
            ->filter()
            ->values()
            ->all();
    }

    /** @return array<string, mixed> */
    private function resumeFacture(array $facture, ?InvoiceSettlement $reglement): array
    {
        return [
            'mois' => substr($facture['period']['from'], 0, 7),
            'label' => $facture['period']['label'],
            'owner' => $facture['owner'],
            'lines' => $facture['lines'],
            'stays' => $facture['stays'],
            'nights' => $facture['nights'],
            'revenue' => $facture['revenue'],
            'due' => $facture['due'],
            'settlement' => $reglement ? [
                'at' => $reglement->settled_at->toIso8601String(),
                'reference' => $reglement->reference,
                'amount' => $reglement->amount,
            ] : null,
        ];
    }

    /** @return array<string, mixed> */
    private function ligneAnnonce(Listing $l, bool $avecProprietaire = true): array
    {
        $couverture = $l->photos->first();

        return [
            'id' => $l->id,
            'slug' => $l->slug,
            'title' => $l->title,
            'status' => $l->status->value,
            'statusLabel' => $this->libelleStatut($l->status, pluriel: false),
            'trustLevel' => $l->trust_level->value,
            'trustLabel' => $l->trust_level->label(),
            'destination' => $l->destination?->name,
            'region' => $l->destination?->region,
            'price' => $l->price,
            'guests' => $l->guests,
            'photos' => $l->photos->count(),
            'cover' => $couverture ? $this->photo($couverture) : null,
            'isDemo' => (bool) $l->is_demo,
            'updatedAt' => $l->updated_at?->toIso8601String(),
            'publicUrl' => $this->urlPublique('/logements/'.$l->slug),
            'owner' => $avecProprietaire && $l->owner ? [
                'id' => $l->owner->id,
                'name' => $l->owner->name,
                'verified' => $l->owner->telephoneVerifie(),
            ] : null,
        ];
    }

    /** @return array<string, mixed> */
    private function ligneReservation(Booking $b): array
    {
        $restantes = $b->status === BookingStatus::Pending && $b->hold_expires_at
            ? max(0, (int) floor(Carbon::now()->diffInMinutes($b->hold_expires_at, false) / 60))
            : null;

        return [
            'reference' => $b->reference,
            'status' => $b->status->value,
            'statusLabel' => $b->status->label(),
            'traveller' => $b->traveller,
            'guests' => $b->guests,
            'arrival' => $b->arrival->toDateString(),
            'departure' => $b->departure->toDateString(),
            'nights' => $b->nights,
            'total' => $b->total,
            'heuresRestantes' => $restantes,
            'nonLu' => $b->owner_read_at === null && $b->status === BookingStatus::Pending,
            'isDemo' => (bool) $b->is_demo,
            'listing' => $b->listing ? ['id' => $b->listing->id, 'title' => $b->listing->title, 'slug' => $b->listing->slug] : null,
            'owner' => $b->listing?->owner ? ['id' => $b->listing->owner->id, 'name' => $b->listing->owner->name] : null,
        ];
    }

    /** @return array<string, mixed> */
    private function carteProprietaire(Owner $o): array
    {
        return [
            'id' => $o->id,
            'name' => $o->name,
            'email' => $o->email,
            'city' => $o->city,
            'portrait' => $o->portrait,
            'telephone' => $this->telephone($o->phone),
            'verified' => $o->telephoneVerifie(),
            'isDemo' => (bool) $o->is_demo,
        ];
    }

    /**
     * Un numéro prêt à servir : lisible, à appeler, à écrire.
     *
     * `tel:` et `wa.me` sont les deux gestes de la vérification — l'appel, et
     * le message quand personne ne décroche. Le `+` saute pour `wa.me`, qui
     * ouvre sinon une conversation vide sans dire pourquoi.
     *
     * @return array<string, mixed>|null
     */
    private function telephone(?string $brut): ?array
    {
        if (! $brut) {
            return null;
        }

        $t = Telephone::depuis($brut);

        return [
            'lisible' => $t?->lisible() ?? $brut,
            'tel' => 'tel:'.($t?->e164() ?? preg_replace('/[^\d+]/', '', $brut)),
            'whatsapp' => $t && $t->estMobile() ? 'https://wa.me/'.ltrim($t->e164(), '+') : null,
            'operateur' => $t?->operateur(),
        ];
    }

    /** @return array<string, mixed> */
    private function photo(Photo $p): array
    {
        return ['key' => $p->key, 'folder' => $p->folder, 'width' => $p->width, 'isAi' => (bool) $p->is_ai, 'caption' => $p->caption];
    }

    /**
     * Une adresse du site public, **depuis l'hôte du site public**. Générée
     * par `route()` depuis le back-office, elle porterait l'hôte `office.…`
     * et tomberait sur la page introuvable du back-office.
     */
    private function urlPublique(string $chemin): string
    {
        return rtrim((string) config('app.url'), '/').$chemin;
    }

    /**
     * Le mot court d'un statut, pour un onglet (au pluriel) ou une ligne. Celui
     * de l'enum — « En attente de vérification » — est écrit pour le
     * propriétaire ; ici on trie une file, et « À vérifier » dit le travail.
     */
    private function libelleStatut(ListingStatus $s, bool $pluriel = true): string
    {
        return match ($s) {
            ListingStatus::Submitted => 'À vérifier',
            ListingStatus::Published => 'En ligne',
            ListingStatus::Draft => $pluriel ? 'Brouillons' : 'Brouillon',
            ListingStatus::Archived => $pluriel ? 'Archivées' : 'Archivée',
        };
    }

    private function mois(Carbon $debut): string
    {
        return $debut->translatedFormat('F Y');
    }

    /**
     * Une page de liste, mise à plat : les lignes et de quoi paginer.
     *
     * @return array<string, mixed>
     */
    private function page(LengthAwarePaginator $page, callable $ligne): array
    {
        return [
            'data' => collect($page->items())->map($ligne)->values()->all(),
            'meta' => [
                'page' => $page->currentPage(),
                'pages' => $page->lastPage(),
                'total' => $page->total(),
                'precedente' => $page->previousPageUrl(),
                'suivante' => $page->nextPageUrl(),
            ],
        ];
    }
}
