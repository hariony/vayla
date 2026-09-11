<?php

namespace Tests\Feature;

use App\Contracts\Repositories\OutboundMessageRepositoryInterface;
use App\Enums\BookingStatus;
use App\Enums\NotificationKind;
use App\Models\Booking;
use App\Models\Listing;
use App\Models\OutboundMessage;
use App\Models\Owner;
use App\Services\Notifications\OwnerNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Les notifications WhatsApp.
 *
 * **C'est le maillon qui rend tout le reste utile.** Une demande a
 * quarante-huit heures pour être répondue ; sans message, il faudrait que le
 * propriétaire pense à ouvrir Vayla dans cette fenêtre — ce qui n'arrivera pas.
 *
 * Trois choses à protéger :
 *
 * 1. **Jamais deux fois le même message.** Un expéditeur qui se répète se met
 *    en sourdine, et le jour où le message compte il n'est plus lu.
 * 2. **Le message est écrit après la transaction.** Écrit dedans, il
 *    annoncerait une demande qui n'existe pas quand la transaction est annulée.
 * 3. **Un numéro injoignable ne met pas de ligne morte dans la file**, qui est
 *    la liste de travail d'une personne.
 */
class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        OutboundMessage::query()->delete();
    }

    private function hanta(): Owner
    {
        return Owner::query()->where('name', 'like', 'Hanta%')->firstOrFail();
    }

    private function demande(string $reference = 'VY-NOT01', int $dans = 260): Booking
    {
        $listing = Listing::query()->where('slug', 'villa-ambatoloaka')->firstOrFail();

        return Booking::create([
            'reference' => $reference,
            'listing_id' => $listing->id,
            'traveller' => 'Rakoto Jean',
            'traveller_phone' => '+261340000099',
            'guests' => 2,
            'arrival' => Carbon::today()->addDays($dans)->toDateString(),
            'departure' => Carbon::today()->addDays($dans + 4)->toDateString(),
            'nights' => 4,
            'price_per_night' => 100000,
            'total' => 400000,
            'commission_rate' => 0.05,
            'status' => BookingStatus::Pending,
            'hold_expires_at' => Carbon::now()->addHours(40),
        ])->load('listing.owner');
    }

    /** Déposer une demande met un message en file, sans qu'on ait à y penser. */
    public function test_une_demande_deposee_met_un_message_en_file(): void
    {
        $listing = Listing::query()->where('slug', 'villa-ambatoloaka')->firstOrFail();

        $this->post("/logements/{$listing->slug}/reserver", [
            'traveller' => 'Claire Fontaine',
            'traveller_phone' => '+33 6 12 45 78 90',
            'guests' => 2,
            'arrival' => Carbon::today()->addDays(270)->toDateString(),
            'departure' => Carbon::today()->addDays(274)->toDateString(),
        ])->assertRedirect();

        $message = OutboundMessage::query()->latest('id')->firstOrFail();

        $this->assertSame(NotificationKind::NouvelleDemande, $message->kind);
        $this->assertSame($this->hanta()->phone, $message->to);
        // Ce qu'on risque à ne rien faire est écrit : c'est la seule phrase
        // qui fait répondre.
        $this->assertStringContainsString('repartent dans votre calendrier', $message->body);
        $this->assertStringContainsString('Villa vue lagon', $message->body);
    }

    /** **La règle qui protège le canal.** Deux fois le même message, et on est en sourdine. */
    public function test_le_meme_message_ne_part_pas_deux_fois(): void
    {
        $demande = $this->demande();
        $notifier = app(OwnerNotifier::class);

        $notifier->nouvelleDemande($demande);
        $notifier->nouvelleDemande($demande);
        $notifier->nouvelleDemande($demande);

        $this->assertSame(1, OutboundMessage::query()
            ->where('kind', NotificationKind::NouvelleDemande->value)->count());
    }

    /**
     * On vérifie **ses propres lignes**, pas un total : le jeu de
     * démonstration porte lui aussi une demande sur le point d'expirer, et un
     * test qui compterait tout casserait au premier ajout dans le seeder.
     */
    public function test_le_rappel_ne_vise_que_les_demandes_qui_expirent_bientot(): void
    {
        $loin = $this->demande('VY-LOIN1');
        $proche = $this->demande('VY-PRES1');
        $proche->forceFill(['hold_expires_at' => Carbon::now()->addHours(4)])->save();

        $this->artisan('vayla:remind-pending-bookings')->assertSuccessful();

        $this->assertTrue($this->rappele($proche), 'La demande proche de l’expiration doit être rappelée.');
        $this->assertFalse($this->rappele($loin), 'Une demande encore loin ne se rappelle pas.');
    }

    /** Une demande déjà expirée ne se rappelle pas : les nuits sont rendues. */
    public function test_une_demande_deja_expiree_ne_se_rappelle_pas(): void
    {
        $expiree = $this->demande();
        $expiree->forceFill(['hold_expires_at' => Carbon::now()->subHour()])->save();

        $this->artisan('vayla:remind-pending-bookings')->assertSuccessful();

        $this->assertFalse($this->rappele($expiree));
    }

    private function rappele(Booking $booking): bool
    {
        return OutboundMessage::query()
            ->where('kind', NotificationKind::DemandeExpireBientot->value)
            ->where('booking_id', $booking->id)
            ->exists();
    }

    /**
     * **Seul le voyageur déclenche une notification.** Prévenir le
     * propriétaire de son propre message serait absurde.
     */
    public function test_seul_le_message_du_voyageur_notifie(): void
    {
        $this->demande();

        $this->post('/reservations/VY-NOT01/messages', ['body' => 'Y a-t-il un lit bébé ?']);
        $this->actingAs($this->hanta(), 'proprietaire')
            ->post('/proprietaire/reservations/VY-NOT01/messages', ['body' => 'Oui, sans supplément.']);

        $messages = OutboundMessage::query()
            ->where('kind', NotificationKind::NouveauMessage->value)->get();

        $this->assertCount(1, $messages);
        $this->assertStringContainsString('lit bébé', $messages->first()->body);
    }

    /**
     * Un numéro injoignable ne met pas de ligne morte dans la file : c'est la
     * liste de travail d'une personne, pas un journal.
     */
    public function test_un_numero_injoignable_ne_met_rien_en_file(): void
    {
        // 020 est un fixe : WhatsApp n'y arrive pas.
        $this->hanta()->forceFill(['phone' => '+261202200001'])->save();

        app(OwnerNotifier::class)->nouvelleDemande($this->demande());

        $this->assertSame(0, OutboundMessage::count());
    }

    /**
     * Le lien `wa.me` part **sans le `+`** : le service l'exige, et un `+`
     * laissé là ouvre une conversation vide sans dire pourquoi.
     */
    public function test_le_lien_whatsapp_est_pret_a_cliquer(): void
    {
        app(OwnerNotifier::class)->lienAcces($this->hanta());

        $lien = OutboundMessage::query()->latest('id')->firstOrFail()->lienWhatsApp();

        $this->assertStringStartsWith('https://wa.me/261', $lien);
        $this->assertStringNotContainsString('+', $lien);
        $this->assertStringContainsString('text=', $lien);
    }

    /** L'urgent passe devant : celui qui envoie à la main n'a pas le temps de trier. */
    public function test_la_file_montre_l_urgent_en_premier(): void
    {
        $notifier = app(OwnerNotifier::class);
        $notifier->lienAcces($this->hanta());

        $proche = $this->demande('VY-PRES2');
        $proche->forceFill(['hold_expires_at' => Carbon::now()->addHours(3)])->save();
        $notifier->demandeExpireBientot($proche->fresh()->load('listing.owner'));

        $file = app(OutboundMessageRepositoryInterface::class)->enAttente();

        $this->assertSame(NotificationKind::DemandeExpireBientot, $file->first()->kind);
    }

    public function test_marquer_envoye_sort_le_message_de_la_file(): void
    {
        app(OwnerNotifier::class)->lienAcces($this->hanta());
        $message = OutboundMessage::query()->latest('id')->firstOrFail();

        $this->artisan('vayla:whatsapp', ['--envoye' => $message->id])->assertSuccessful();

        $this->assertNotNull($message->fresh()->sent_at);
        $this->assertTrue($message->fresh()->envoye());
        $this->artisan('vayla:whatsapp')->expectsOutputToContain('Rien à envoyer.');
    }
}
