<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\MessageAuthor;
use App\Models\Booking;
use App\Models\BookingMessage;
use App\Models\Listing;
use App\Models\Owner;
use App\Services\ConversationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * L'échange écrit autour d'une réservation.
 *
 * Ce que ces tests protègent :
 *
 * 1. **Le fil est attaché à une réservation, et à une seule.** Une session
 *    valide plus une référence devinée ne doit pas ouvrir la conversation
 *    d'un confrère — les références sont courtes et se dictent au téléphone.
 * 2. **Le voyageur n'a pas de compte**, c'est délibéré : sa référence tient
 *    lieu de droit d'accès, et la limite de débit est ce qui tient la porte.
 * 3. **Le compteur redescend tout seul.** Un non-lu qui ne s'efface qu'avec
 *    un bouton dédié finit par être ignoré, donc par ne plus rien signaler.
 */
class ConversationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    private function hanta(): Owner
    {
        return Owner::query()->where('name', 'like', 'Hanta%')->firstOrFail();
    }

    private function connectee(): static
    {
        return $this->actingAs($this->hanta(), 'proprietaire');
    }

    private function reservation(string $slug = 'villa-ambatoloaka', string $ref = 'VY-MSG01'): Booking
    {
        $listing = Listing::query()->where('slug', $slug)->firstOrFail();

        return Booking::create([
            'reference' => $ref,
            'listing_id' => $listing->id,
            'traveller' => 'Rakoto Jean',
            'traveller_phone' => '+261340000099',
            'guests' => 2,
            'arrival' => Carbon::today()->addDays(250)->toDateString(),
            'departure' => Carbon::today()->addDays(254)->toDateString(),
            'nights' => 4,
            'price_per_night' => 100000,
            'total' => 400000,
            'commission_rate' => 0.05,
            'status' => BookingStatus::Accepted,
        ]);
    }

    /**
     * Le message déposé avec la demande **est** le premier message du fil.
     * Le laisser dans sa colonne aurait fait deux endroits où vivent les mots
     * d'un voyageur, et l'écran aurait fini par n'en montrer qu'un des deux.
     */
    public function test_le_message_de_la_demande_ouvre_le_fil(): void
    {
        $demo = Booking::query()->whereNotNull('message')->where('is_demo', true)->first();

        $this->assertNotNull($demo, 'Le jeu de démonstration doit porter au moins un message.');
        $this->assertSame($demo->message, $demo->messages->first()?->body);
        $this->assertSame(MessageAuthor::Traveller, $demo->messages->first()?->author);
    }

    public function test_le_proprietaire_repond_dans_le_fil(): void
    {
        $this->reservation();

        $this->connectee()
            ->post('/proprietaire/reservations/VY-MSG01/messages', ['body' => 'Le transfert est possible, je vous appelle.'])
            ->assertRedirect()
            ->assertSessionHas('succes');

        $message = BookingMessage::query()->latest('id')->firstOrFail();

        $this->assertSame(MessageAuthor::Owner, $message->author);
        $this->assertSame('Le transfert est possible, je vous appelle.', $message->body);
    }

    /** Le voyageur écrit sans compte : sa référence tient lieu de droit d'accès. */
    public function test_le_voyageur_ecrit_sans_compte(): void
    {
        $this->reservation();

        $this->post('/reservations/VY-MSG01/messages', ['body' => 'Bonjour, y a-t-il un lit bébé ?'])
            ->assertRedirect()
            ->assertSessionHas('succes');

        $this->assertSame(MessageAuthor::Traveller, BookingMessage::query()->latest('id')->first()->author);
    }

    public function test_les_deux_cotes_lisent_le_meme_fil(): void
    {
        $this->reservation();
        $this->post('/reservations/VY-MSG01/messages', ['body' => 'Bonjour, y a-t-il un lit bébé ?']);
        $this->connectee()->post('/proprietaire/reservations/VY-MSG01/messages', ['body' => 'Oui, sans supplément.']);

        // Côté propriétaire : sa réponse est marquée « moi ».
        $this->connectee()->get('/proprietaire/reservations/VY-MSG01')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Owner/Bookings/Show')
                ->has('messages', 2)
                ->where('messages.0.moi', false)
                ->where('messages.1.moi', true));

        // Côté voyageur : c'est l'inverse, et c'est la seule différence.
        $this->get('/reservations/VY-MSG01')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('messages', 2)
                ->where('messages.0.moi', true)
                ->where('messages.1.moi', false));
    }

    /**
     * **Le test qui compte pour la sécurité.** Un slug ou une référence se
     * lisent ailleurs : ils ne doivent pas ouvrir la conversation d'un autre.
     */
    public function test_on_n_ouvre_pas_le_fil_d_un_autre_proprietaire(): void
    {
        // `front-de-mer-amborovy` appartient à Voahangy, pas à Hanta.
        $this->reservation('front-de-mer-amborovy', 'VY-AUTRE');

        $this->connectee()->get('/proprietaire/reservations/VY-AUTRE')->assertNotFound();
        $this->connectee()
            ->post('/proprietaire/reservations/VY-AUTRE/messages', ['body' => 'Bonjour'])
            ->assertNotFound();

        $this->assertSame(0, BookingMessage::query()->where('author', MessageAuthor::Owner)->count());
    }

    public function test_une_reference_inconnue_n_ouvre_aucun_fil(): void
    {
        $this->post('/reservations/VY-XXXXX/messages', ['body' => 'Bonjour'])->assertNotFound();
        $this->connectee()->get('/proprietaire/reservations/VY-XXXXX')->assertNotFound();
    }

    /**
     * **Ouvrir vaut lecture.** Un compteur qui ne redescend qu'avec un bouton
     * dédié finit par être ignoré, donc par ne plus rien signaler.
     */
    public function test_ouvrir_le_fil_efface_le_non_lu(): void
    {
        $this->reservation();
        $this->post('/reservations/VY-MSG01/messages', ['body' => 'Une question.']);

        $this->connectee()->get('/proprietaire/reservations')
            ->assertInertia(fn ($page) => $page->where('bookings', fn ($b) => collect($b)
                ->firstWhere('reference', 'VY-MSG01')['nonLus'] === 1));

        $this->connectee()->get('/proprietaire/reservations/VY-MSG01')->assertOk();

        $this->connectee()->get('/proprietaire/reservations')
            ->assertInertia(fn ($page) => $page->where('bookings', fn ($b) => collect($b)
                ->firstWhere('reference', 'VY-MSG01')['nonLus'] === 0));
    }

    /** Son propre message ne doit jamais compter comme non lu pour soi. */
    public function test_ecrire_vaut_lecture(): void
    {
        $this->reservation();

        $this->connectee()->post('/proprietaire/reservations/VY-MSG01/messages', ['body' => 'Bonjour, à bientôt.']);

        $this->connectee()->get('/proprietaire/reservations')
            ->assertInertia(fn ($page) => $page->where('bookings', fn ($b) => collect($b)
                ->firstWhere('reference', 'VY-MSG01')['nonLus'] === 0));
    }

    /**
     * **La pastille compte les conversations, pas les messages.** « 2 » veut
     * dire « deux échanges vous attendent », pas « quatorze lignes de texte » :
     * un compte de messages ferait paniquer pour un voyageur bavard.
     *
     * On mesure l'écart plutôt qu'une valeur absolue : le jeu de
     * démonstration porte déjà des messages, et un test qui l'ignorerait
     * casserait au premier ajout dans le seeder.
     */
    public function test_le_compteur_de_l_onglet_compte_les_conversations(): void
    {
        $avant = app(ConversationService::class)->nonLus($this->hanta());

        $this->reservation();
        $this->post('/reservations/VY-MSG01/messages', ['body' => 'Une question.']);
        $this->post('/reservations/VY-MSG01/messages', ['body' => 'Et une autre.']);
        $this->post('/reservations/VY-MSG01/messages', ['body' => 'Une troisième.']);

        $this->connectee()->get('/proprietaire')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('ownerUnread', $avant + 1));
    }

    public function test_un_message_vide_ou_trop_long_est_refuse(): void
    {
        $this->reservation();

        $this->post('/reservations/VY-MSG01/messages', ['body' => ' '])->assertSessionHasErrors('body');
        $this->post('/reservations/VY-MSG01/messages', ['body' => str_repeat('a', 2001)])
            ->assertSessionHasErrors('body');

        $this->assertSame(0, BookingMessage::query()->where('booking_id', Booking::query()
            ->where('reference', 'VY-MSG01')->value('id'))->count());
    }
}
