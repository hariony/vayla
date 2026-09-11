<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\MessageAuthor;
use App\Models\Booking;
use App\Models\BookingMessage;
use App\Models\Listing;
use App\Models\Owner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Les deux boîtes : celle du propriétaire, celle du voyageur.
 *
 * **Elles existent parce qu'un message se rate.** Les fils ne vivaient que
 * *dans* chaque réservation : pour savoir si quelqu'un attendait, il fallait
 * ouvrir les réservations une par une. Un voyageur sans réponse ne revient
 * pas — et il l'écrira dans sa confirmation de séjour, qui est publique.
 *
 * Ce que ces tests protègent, dans l'ordre de ce qui coûterait le plus cher :
 *
 * 1. **Une boîte ne montre que ce qui appartient à celui qui la lit.** C'est
 *    la même portée de sécurité que partout dans l'espace : les identifiants
 *    des logements côté propriétaire, l'adresse côté voyageur.
 * 2. **Le non-lu redescend tout seul** — ouvrir vaut lecture. Un compteur qui
 *    ne s'efface qu'avec un bouton dédié finit par être ignoré, donc par ne
 *    plus rien signaler.
 * 3. **La boîte se trie sur le dernier message**, pas sur la réservation :
 *    une conversation qui reprend six mois après doit remonter.
 */
class InboxTest extends TestCase
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

    /** Une réservation sur le logement donné, éventuellement avec un message. */
    private function reservation(string $slug, string $ref, string $email, ?string $mot = null, ?MessageAuthor $auteur = null): Booking
    {
        $listing = Listing::query()->where('slug', $slug)->firstOrFail();

        $booking = Booking::create([
            'reference' => $ref,
            'listing_id' => $listing->id,
            'traveller' => 'Rakoto Jean',
            'traveller_phone' => '+261340000099',
            'traveller_email' => $email,
            'guests' => 2,
            'arrival' => Carbon::today()->addDays(250)->toDateString(),
            'departure' => Carbon::today()->addDays(254)->toDateString(),
            'nights' => 4,
            'price_per_night' => 100000,
            'total' => 400000,
            'commission_rate' => 0.05,
            'status' => BookingStatus::Accepted,
        ]);

        if ($mot !== null) {
            BookingMessage::create([
                'booking_id' => $booking->id,
                'author' => $auteur ?? MessageAuthor::Traveller,
                'body' => $mot,
            ]);
        }

        return $booking;
    }

    /**
     * Les lignes de la boîte, par référence.
     *
     * **Jamais un comptage absolu** : le jeu de démonstration porte déjà des
     * conversations — le message déposé avec une demande ouvre le fil — et un
     * test qui compte les lignes casserait au premier séjour ajouté au
     * seeder, sans rien apprendre.
     *
     * @return array<int, array<string, mixed>>
     */
    private function boite(string $url): array
    {
        $reponse = $this->get($url)->assertOk();

        return $reponse->viewData('page')['props']['conversations'];
    }

    /** @param  array<int, array<string, mixed>>  $lignes */
    private function ligne(array $lignes, string $reference): ?array
    {
        return collect($lignes)->firstWhere('reference', $reference);
    }

    /** Le logement d'un autre propriétaire que Hanta. */
    private function chezUnAutre(): Listing
    {
        return Listing::query()
            ->where('owner_id', '!=', $this->hanta()->id)
            ->whereNotNull('owner_id')
            ->firstOrFail();
    }

    public function test_la_boite_du_proprietaire_ne_montre_que_ses_conversations(): void
    {
        $this->reservation('villa-ambatoloaka', 'VY-BX001', 'jean@example.com', 'Bonjour, la piscine est-elle chauffée ?');

        // La même chose chez un confrère : elle ne doit jamais apparaître.
        $autre = $this->chezUnAutre();
        $chezLautre = Booking::create([
            'reference' => 'VY-BX002',
            'listing_id' => $autre->id,
            'traveller' => 'Autre voyageur',
            'traveller_phone' => '+261340000098',
            'traveller_email' => 'autre@example.com',
            'guests' => 2,
            'arrival' => Carbon::today()->addDays(260)->toDateString(),
            'departure' => Carbon::today()->addDays(262)->toDateString(),
            'nights' => 2,
            'price_per_night' => 90000,
            'total' => 180000,
            'commission_rate' => 0.05,
            'status' => BookingStatus::Accepted,
        ]);
        BookingMessage::create([
            'booking_id' => $chezLautre->id,
            'author' => MessageAuthor::Traveller,
            'body' => 'Message qui ne regarde pas Hanta.',
        ]);

        $boite = $this->actingAs($this->hanta(), 'proprietaire')->boite('/proprietaire/messages');

        $mienne = $this->ligne($boite, 'VY-BX001');

        $this->assertNotNull($mienne, 'La conversation de Hanta doit figurer dans sa boîte.');
        $this->assertTrue($mienne['nonLu'], 'Un message du voyageur jamais ouvert attend une réponse.');
        $this->assertNull(
            $this->ligne($boite, 'VY-BX002'),
            'La conversation d’un confrère n’a rien à faire dans cette boîte.'
        );
    }

    public function test_la_boite_du_voyageur_suit_son_adresse(): void
    {
        $this->reservation('villa-ambatoloaka', 'VY-BX003', 'moi@example.com', 'Est-ce qu’on peut arriver tard ?');
        $this->reservation('villa-ambatoloaka', 'VY-BX004', 'quelquun-dautre@example.com', 'Bonjour !');

        $moi = User::create(['email' => 'moi@example.com']);

        $boite = $this->actingAs($moi)->boite('/mes-messages');

        $this->assertNotNull($this->ligne($boite, 'VY-BX003'));
        $this->assertNull(
            $this->ligne($boite, 'VY-BX004'),
            'Une réservation faite avec une autre adresse n’appartient pas à ce compte.'
        );
    }

    /**
     * **Une réservation sans message n'est pas une conversation.** La faire
     * figurer donnerait une boîte pleine de lignes muettes, où le vrai message
     * se perdrait.
     */
    public function test_une_reservation_sans_message_n_entre_pas_dans_la_boite(): void
    {
        $this->reservation('villa-ambatoloaka', 'VY-BX005', 'muet@example.com');

        $boite = $this->actingAs($this->hanta(), 'proprietaire')->boite('/proprietaire/messages');

        $this->assertNull($this->ligne($boite, 'VY-BX005'));
    }

    /**
     * **Ouvrir vaut lecture**, et la boîte le reflète tout de suite : c'est ce
     * qui permet de se passer d'un bouton « marquer comme lu ».
     */
    public function test_ouvrir_la_reservation_efface_le_non_lu(): void
    {
        $this->reservation('villa-ambatoloaka', 'VY-BX006', 'jean@example.com', 'Une question.');

        $this->actingAs($this->hanta(), 'proprietaire')
            ->get('/proprietaire/reservations/VY-BX006')
            ->assertOk();

        $boite = $this->actingAs($this->hanta(), 'proprietaire')->boite('/proprietaire/messages');

        $this->assertFalse($this->ligne($boite, 'VY-BX006')['nonLu']);
    }

    /**
     * **Sa propre réponse reste visible dans la boîte.** Ne montrer que le
     * dernier message *reçu* ferait disparaître ce qu'on vient d'écrire : on
     * ne saurait plus si on a répondu, ce qui est exactement la question qu'on
     * se pose en ouvrant une boîte.
     */
    public function test_l_extrait_montre_le_dernier_message_meme_le_sien(): void
    {
        $booking = $this->reservation('villa-ambatoloaka', 'VY-BX007', 'jean@example.com', 'Bonjour ?');

        BookingMessage::create([
            'booking_id' => $booking->id,
            'author' => MessageAuthor::Owner,
            'body' => 'Oui, tout à fait.',
        ]);

        $ligne = $this->ligne(
            $this->actingAs($this->hanta(), 'proprietaire')->boite('/proprietaire/messages'),
            'VY-BX007'
        );

        $this->assertSame('Oui, tout à fait.', $ligne['extrait']);
        $this->assertSame('owner', $ligne['auteur']);
        // Son propre message n'attend personne.
        $this->assertFalse($ligne['nonLu']);
    }

    public function test_les_boites_sont_fermees_aux_visiteurs(): void
    {
        $this->get('/mes-messages')->assertRedirect('/connexion/client');
        $this->get('/proprietaire/messages')->assertRedirect('/proprietaire/connexion');
    }
}
