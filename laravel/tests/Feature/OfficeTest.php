<?php

namespace Tests\Feature;

use App\Enums\AdminActionKind;
use App\Enums\BookingStatus;
use App\Enums\ListingStatus;
use App\Enums\MessageAuthor;
use App\Enums\NotificationKind;
use App\Enums\TrustLevel;
use App\Models\Admin;
use App\Models\AdminAction;
use App\Models\Booking;
use App\Models\InvoiceSettlement;
use App\Models\Listing;
use App\Models\OutboundMessage;
use App\Models\Owner;
use App\Models\StayConfirmation;
use App\Services\Office\OfficeAuthService;
use App\Services\OwnerListingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Le back-office, sur son propre hôte.
 *
 * Trois familles de règles, et chacune protège quelque chose de précis :
 *
 * 1. **L'isolement.** L'hôte du back-office ne sert jamais le site public, les
 *    sessions voyageur et propriétaire n'y ouvrent rien, et la session d'un
 *    administrateur n'ouvre aucun espace public. Un seul de ces murs manquant,
 *    et une faille du site devient une faille de l'outil qui publie.
 * 2. **La porte.** Une adresse, un mot de passe — et jamais un mot sur
 *    laquelle des deux valeurs est fausse. Un mot de passe provisoire n'ouvre
 *    qu'un écran : celui qui demande d'en choisir un.
 * 3. **L'échelle.** C'est le seul endroit où `trust_level` s'écrit : chaque
 *    règle qui empêche la jauge de mentir est tenue ici.
 */
class OfficeTest extends TestCase
{
    use RefreshDatabase;

    private const HOTE = 'http://office.localhost';

    private const MOT_DE_PASSE = 'une-phrase-de-passe-assez-longue';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        // Le contrôle des fuites connues interroge un service en ligne : les
        // tests ne sortent pas du conteneur. Une réponse vide dit « jamais vu ».
        Http::fake();
    }

    private function admin(string $email = 'equipe@vayla.test', string $nom = 'Voahirana Andria'): Admin
    {
        return Admin::query()->firstOrCreate(['email' => $email], [
            'name' => $nom,
            'password' => self::MOT_DE_PASSE,
            'password_set_at' => now(),
        ]);
    }

    private function office(string $chemin): string
    {
        return self::HOTE.$chemin;
    }

    private function connecte(): static
    {
        return $this->actingAs($this->admin(), 'admin');
    }

    /** Une fiche envoyée par son propriétaire, numéro pas encore vérifié. */
    private function ficheEnvoyee(): Listing
    {
        $listing = Listing::query()->where('is_demo', true)->firstOrFail();
        $listing->update(['status' => ListingStatus::Submitted, 'trust_level' => TrustLevel::Declared]);
        $listing->owner->forceFill(['phone_verified_at' => null])->save();
        $listing->confirmations()->delete();

        return $listing->fresh();
    }

    // ── L'isolement ─────────────────────────────────────────────────────

    public function test_l_hote_du_back_office_ne_sert_jamais_le_site_public(): void
    {
        $this->get($this->office('/'))->assertRedirect($this->office('/connexion'));

        // Les chemins du site n'existent pas ici : ni le catalogue, ni
        // l'espace propriétaire, ni les écrans du compte voyageur.
        foreach (['/logements', '/proprietaire', '/mes-reservations', '/destinations/nosy-be'] as $chemin) {
            $this->get($this->office($chemin))->assertNotFound();
        }

        // Et le site, lui, n'a pas bougé.
        $this->get('/')->assertOk();
        $this->get('/logements')->assertOk();
    }

    public function test_les_sessions_du_site_n_ouvrent_pas_le_back_office(): void
    {
        $owner = Owner::query()->firstOrFail();

        $this->actingAs($owner, 'proprietaire')
            ->get($this->office('/annonces'))
            ->assertRedirect($this->office('/connexion'));
    }

    public function test_la_session_d_un_administrateur_n_ouvre_aucun_espace_public(): void
    {
        $this->connecte()->get('/proprietaire')->assertRedirect('/proprietaire/connexion');
        $this->connecte()->get('/mes-reservations')->assertRedirect('/connexion/client');
    }

    /**
     * **Aucune rubrique décorative.** Chaque entrée de la colonne répond, sous
     * la garde `admin` — la règle des menus du site, appliquée ici.
     */
    public function test_chaque_rubrique_de_la_colonne_repond(): void
    {
        $source = file_get_contents(resource_path('js/Support/office.js'));
        preg_match_all("/href: '([^']+)'/", $source, $liens);

        $this->assertNotEmpty($liens[1]);

        foreach ($liens[1] as $href) {
            $this->connecte()->get($this->office($href))->assertOk();
        }
    }

    public function test_les_ecrans_de_detail_repondent(): void
    {
        $listing = Listing::query()->firstOrFail();
        $owner = Owner::query()->firstOrFail();
        $booking = Booking::query()->firstOrFail();

        $this->connecte()->get($this->office("/annonces/{$listing->id}"))->assertOk();
        $this->connecte()->get($this->office("/proprietaires/{$owner->id}"))->assertOk();
        $this->connecte()->get($this->office("/reservations/{$booking->reference}"))->assertOk();
    }

    /** Rien n'arrive au front en modèle : la clé d'accès ne sort jamais. */
    public function test_la_cle_d_acces_ne_sort_jamais_du_serveur(): void
    {
        $owner = Owner::query()->whereNotNull('access_key')->firstOrFail();

        $page = $this->connecte()->get($this->office("/proprietaires/{$owner->id}"))->assertOk();

        $this->assertStringNotContainsString($owner->access_key, $page->getContent());
    }

    // ── La porte ────────────────────────────────────────────────────────

    public function test_on_entre_par_son_adresse_et_son_mot_de_passe(): void
    {
        $this->admin();

        $this->post($this->office('/connexion'), ['email' => 'Equipe@Vayla.test', 'password' => self::MOT_DE_PASSE])
            ->assertRedirect(route('office.home'));

        $this->assertAuthenticatedAs($this->admin(), 'admin');
        $this->assertNotNull($this->admin()->fresh()->last_login_at);
    }

    /**
     * **L'échec ne dit jamais laquelle des deux valeurs est fausse.** Une
     * adresse inconnue et un mauvais mot de passe reçoivent le même message,
     * sur le même champ : sinon le formulaire devient un annuaire de l'équipe.
     */
    public function test_un_echec_ne_dit_pas_quelle_valeur_est_fausse(): void
    {
        $this->admin();

        $inconnue = $this->post($this->office('/connexion'), ['email' => 'curieux@exemple.test', 'password' => self::MOT_DE_PASSE]);
        $mauvais = $this->post($this->office('/connexion'), ['email' => 'equipe@vayla.test', 'password' => 'pas-le-bon-mot-de-passe']);

        $inconnue->assertSessionHasErrors(['email' => 'Adresse ou mot de passe incorrect.']);
        $mauvais->assertSessionHasErrors(['email' => 'Adresse ou mot de passe incorrect.']);
        $this->assertGuest('admin');
    }

    public function test_apres_cinq_essais_la_porte_dit_quand_reessayer(): void
    {
        $this->admin();

        foreach (range(1, 5) as $essai) {
            $this->post($this->office('/connexion'), ['email' => 'equipe@vayla.test', 'password' => "faux-{$essai}"]);
        }

        // Même le bon mot de passe attend : sinon la limite ne protège rien.
        $this->post($this->office('/connexion'), ['email' => 'equipe@vayla.test', 'password' => self::MOT_DE_PASSE])
            ->assertSessionHasErrors('email');
        $this->assertStringContainsString('Réessayez dans', session('errors')->first('email'));
        $this->assertGuest('admin');
    }

    /**
     * **Un mot de passe provisoire n'ouvre qu'un écran.** Il a été vu par
     * quelqu'un d'autre ; publier sous un secret partagé ferait mentir le
     * journal sur qui a agi.
     */
    public function test_un_mot_de_passe_provisoire_n_ouvre_que_mon_compte(): void
    {
        $membre = Admin::create(['name' => 'Nouveau', 'email' => 'nouveau@vayla.test']);
        $provisoire = app(OfficeAuthService::class)->provisoire($membre);

        $this->post($this->office('/connexion'), ['email' => 'nouveau@vayla.test', 'password' => $provisoire])
            ->assertRedirect(route('office.home'));

        $this->get($this->office('/'))->assertRedirect(route('office.account'));
        $this->get($this->office('/annonces'))->assertRedirect(route('office.account'));
        $this->get($this->office('/compte'))->assertOk();

        $this->post($this->office('/compte'), [
            'current_password' => $provisoire,
            'password' => 'ma-propre-phrase-de-passe',
            'password_confirmation' => 'ma-propre-phrase-de-passe',
        ])->assertRedirect(route('office.home'));

        $this->assertTrue($membre->fresh()->motDePasseChoisi());
        $this->get($this->office('/'))->assertOk();
    }

    public function test_changer_de_mot_de_passe_exige_l_actuel_et_douze_caracteres(): void
    {
        $this->connecte()->post($this->office('/compte'), [
            'current_password' => 'pas-le-bon',
            'password' => 'une-autre-phrase-assez-longue',
            'password_confirmation' => 'une-autre-phrase-assez-longue',
        ])->assertSessionHasErrors('current_password');

        $this->connecte()->post($this->office('/compte'), [
            'current_password' => self::MOT_DE_PASSE,
            'password' => 'court',
            'password_confirmation' => 'court',
        ])->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check(self::MOT_DE_PASSE, $this->admin()->fresh()->password));
    }

    /**
     * La porte a son propre décor, un vrai champ mot de passe — et ni
     * connexion sociale ni « rester connecté ».
     */
    public function test_la_porte_a_son_decor_et_un_champ_mot_de_passe(): void
    {
        $source = file_get_contents(resource_path('js/Pages/Office/Login.vue'));
        preg_match('/<template>(.*)<\/template>/s', $source, $balisage);

        $this->assertStringContainsString('<OfficeGate', $balisage[1]);
        $this->assertStringNotContainsString('<AccessShell', $balisage[1], 'La porte du back-office ne doit pas ressembler à celles du site.');
        $this->assertStringContainsString('autocomplete="current-password"', $balisage[1]);
        $this->assertStringNotContainsString('SocialButtons', $balisage[1]);
        $this->assertStringNotContainsString('remember', $balisage[1]);
    }

    public function test_la_deconnexion_est_un_post(): void
    {
        $this->connecte()->get($this->office('/deconnexion'))->assertNotFound();

        $this->connecte()->post($this->office('/deconnexion'))->assertRedirect($this->office('/connexion'));
        $this->assertGuest('admin');
    }

    // ── L'échelle ───────────────────────────────────────────────────────

    public function test_on_ne_publie_pas_une_annonce_seulement_declaree(): void
    {
        $listing = $this->ficheEnvoyee();

        $this->connecte()->post($this->office("/annonces/{$listing->id}/publier"))
            ->assertSessionHas('erreur');

        $this->assertSame(ListingStatus::Submitted, $listing->fresh()->status);
    }

    /** Le niveau 2 se lit « numéro et identité vérifiés » : il attend l'appel. */
    public function test_le_niveau_deux_attend_la_verification_du_numero(): void
    {
        $listing = $this->ficheEnvoyee();

        $this->connecte()->post($this->office("/annonces/{$listing->id}/niveau"), ['level' => 2])
            ->assertSessionHas('erreur');
        $this->assertSame(TrustLevel::Declared, $listing->fresh()->trust_level);

        $this->connecte()->post($this->office("/proprietaires/{$listing->owner_id}/verifier"))
            ->assertSessionHas('succes');

        $this->connecte()->post($this->office("/annonces/{$listing->id}/niveau"), ['level' => 2, 'note' => 'Appel de 10 min.'])
            ->assertSessionHas('succes');
        $this->assertSame(TrustLevel::Contact, $listing->fresh()->trust_level);

        $this->connecte()->post($this->office("/annonces/{$listing->id}/publier"))->assertSessionHas('succes');
        $this->assertSame(ListingStatus::Published, $listing->fresh()->status);

        // Chaque geste a laissé sa ligne, signée.
        $this->assertSame(
            [AdminActionKind::PhoneVerified, AdminActionKind::TrustLevelChanged, AdminActionKind::ListingPublished],
            AdminAction::query()->orderBy('id')->get()->pluck('kind')->all(),
        );
        $this->assertSame('Voahirana Andria', AdminAction::query()->value('admin_name'));
    }

    /** Le niveau 4 s'atteint par les voyageurs, il ne s'attribue pas. */
    public function test_le_niveau_quatre_ne_s_attribue_pas(): void
    {
        $listing = $this->ficheEnvoyee();
        $listing->owner->forceFill(['phone_verified_at' => now()])->save();

        $this->connecte()->post($this->office("/annonces/{$listing->id}/niveau"), ['level' => 4])
            ->assertSessionHas('erreur');
        $this->assertSame(TrustLevel::Declared, $listing->fresh()->trust_level);
    }

    /** Avec des confirmations, l'annonce ne redescend plus : elles la contrediraient. */
    public function test_une_annonce_confirmee_par_des_voyageurs_ne_redescend_pas(): void
    {
        $listing = Listing::query()->where('trust_level', TrustLevel::Proven->value)->has('confirmations')->firstOrFail();

        $this->connecte()->post($this->office("/annonces/{$listing->id}/niveau"), ['level' => 3])
            ->assertSessionHas('erreur');
        $this->assertSame(TrustLevel::Proven, $listing->fresh()->trust_level);
    }

    public function test_une_annonce_en_ligne_ne_passe_pas_sous_le_niveau_deux(): void
    {
        $listing = Listing::query()
            ->where('status', ListingStatus::Published->value)
            ->where('trust_level', TrustLevel::Contact->value)
            ->doesntHave('confirmations')
            ->first() ?? tap($this->ficheEnvoyee(), fn (Listing $l) => $l->update(['status' => ListingStatus::Published, 'trust_level' => TrustLevel::Contact]));

        $this->connecte()->post($this->office("/annonces/{$listing->id}/niveau"), ['level' => 1])
            ->assertSessionHas('erreur');
        $this->assertSame(TrustLevel::Contact, $listing->fresh()->trust_level);
    }

    /**
     * **Renvoyer exige un motif, et le propriétaire le lit.** Le motif
     * disparaît quand il renvoie sa fiche : il a été entendu.
     */
    public function test_le_motif_du_renvoi_arrive_chez_le_proprietaire(): void
    {
        $listing = $this->ficheEnvoyee();

        $this->connecte()->post($this->office("/annonces/{$listing->id}/renvoyer"), ['reason' => ''])
            ->assertSessionHasErrors('reason');

        $motif = 'La photo de la chambre 2 est floue, merci d’en reprendre une.';
        $this->connecte()->post($this->office("/annonces/{$listing->id}/renvoyer"), ['reason' => $motif])
            ->assertSessionHas('succes');

        $listing->refresh();
        $this->assertSame(ListingStatus::Draft, $listing->status);
        $this->assertSame($motif, $listing->review_note);

        $this->actingAs($listing->owner, 'proprietaire')
            ->get('/proprietaire/logements')
            ->assertInertia(fn ($page) => $page->where('listings', fn ($liste) => collect($liste)->firstWhere('slug', $listing->slug)['reviewNote'] === $motif));

        $this->actingAs($listing->owner, 'proprietaire')
            ->get("/proprietaire/logements/{$listing->slug}/modifier")
            ->assertInertia(fn ($page) => $page->where('listing.reviewNote', $motif));

        // Il renvoie sa fiche : le motif a servi.
        $this->assertSame([], app(OwnerListingService::class)->soumettre($listing));
        $this->assertNull($listing->fresh()->review_note);
    }

    public function test_seule_une_fiche_en_attente_se_renvoie(): void
    {
        $listing = Listing::query()->where('status', ListingStatus::Published->value)->firstOrFail();

        $this->connecte()->post($this->office("/annonces/{$listing->id}/renvoyer"), ['reason' => 'Une raison assez longue pour passer.'])
            ->assertSessionHas('erreur');
        $this->assertSame(ListingStatus::Published, $listing->fresh()->status);
    }

    public function test_archiver_retire_l_annonce_du_site(): void
    {
        $listing = Listing::query()->where('status', ListingStatus::Published->value)->firstOrFail();

        $this->connecte()->post($this->office("/annonces/{$listing->id}/archiver"))->assertSessionHas('succes');

        $this->assertSame(ListingStatus::Archived, $listing->fresh()->status);
        $this->get("/logements/{$listing->slug}")->assertNotFound();
    }

    // ── Les séjours ─────────────────────────────────────────────────────

    /** Annuler, c'est aussi l'écrire dans le fil : les deux parties le lisent. */
    public function test_annuler_une_reservation_l_ecrit_dans_le_fil(): void
    {
        $booking = Booking::query()->whereIn('status', [BookingStatus::Pending->value, BookingStatus::Accepted->value])->firstOrFail();

        $this->connecte()->post($this->office("/reservations/{$booking->reference}/annuler"), ['reason' => 'Le logement est inondé depuis la tempête.'])
            ->assertSessionHas('succes');

        $booking->refresh();
        $this->assertSame(BookingStatus::Cancelled, $booking->status);
        $this->assertTrue($booking->messages()->where('author', MessageAuthor::Vayla->value)->exists());
        $this->assertTrue(AdminAction::query()->where('kind', AdminActionKind::BookingCancelled->value)->exists());
    }

    public function test_une_reservation_close_ne_s_annule_pas_deux_fois(): void
    {
        $booking = Booking::query()->firstOrFail();
        $booking->update(['status' => BookingStatus::Declined]);

        $this->connecte()->post($this->office("/reservations/{$booking->reference}/annuler"), ['reason' => 'Une raison assez longue pour passer.'])
            ->assertSessionHas('erreur');
    }

    public function test_vayla_ecrit_dans_le_fil_sans_marquer_comme_lu(): void
    {
        $booking = Booking::query()->firstOrFail();
        $booking->update(['owner_read_at' => null]);

        $this->connecte()->get($this->office("/reservations/{$booking->reference}"))->assertOk();
        $this->assertNull($booking->fresh()->owner_read_at);

        $this->connecte()->post($this->office("/reservations/{$booking->reference}/messages"), ['body' => 'Bonjour à tous les deux.'])
            ->assertSessionHas('succes');
        $this->assertSame(MessageAuthor::Vayla, $booking->messages()->latest('id')->first()->author);
    }

    // ── Les relais ──────────────────────────────────────────────────────

    /**
     * **Le lien d'accès part avec l'adresse du site, pas celle du
     * back-office.** Généré depuis une requête `office.…`, il aurait pris cet
     * hôte — une page introuvable envoyée à un propriétaire.
     */
    public function test_le_lien_renvoye_depuis_le_back_office_pointe_sur_le_site(): void
    {
        $owner = Owner::query()->whereNotNull('phone')->get()->first(fn (Owner $o) => $o->telephone()?->estMobile());

        $this->connecte()->post($this->office("/proprietaires/{$owner->id}/lien"))->assertSessionHas('succes');

        $message = OutboundMessage::query()->where('kind', NotificationKind::LienAcces->value)->latest('id')->firstOrFail();

        $this->assertStringContainsString(rtrim(config('app.url'), '/').'/proprietaire/acces/', $message->body);
        $this->assertStringNotContainsString('office.', $message->body);
    }

    public function test_marquer_un_message_whatsapp_comme_envoye(): void
    {
        $message = OutboundMessage::create([
            'kind' => NotificationKind::Test, 'to' => '+261340000001', 'body' => 'Test',
        ]);

        $this->connecte()->post($this->office("/whatsapp/{$message->id}/envoye"))->assertSessionHas('succes');
        $this->assertNotNull($message->fresh()->sent_at);

        $this->connecte()->post($this->office("/whatsapp/{$message->id}/envoye"))->assertSessionHas('erreur');
    }

    // ── La facturation ──────────────────────────────────────────────────

    private function sejourDuMoisDernier(): Booking
    {
        $listing = Listing::query()->whereNotNull('owner_id')->firstOrFail();
        $depart = Carbon::today()->subMonthNoOverflow()->startOfMonth()->addDays(10);

        $booking = Booking::create([
            'reference' => 'VY-FCT23',
            'listing_id' => $listing->id,
            'traveller' => 'Rakoto',
            'traveller_phone' => '+261341234567',
            'guests' => 2,
            'arrival' => $depart->copy()->subDays(3),
            'departure' => $depart,
            'nights' => 3,
            'price_per_night' => 100000,
            'total' => 300000,
            'commission_rate' => 0.05,
            'status' => BookingStatus::Completed,
            'completed_at' => $depart,
        ]);

        StayConfirmation::create([
            'listing_id' => $listing->id, 'booking_id' => $booking->id, 'traveller' => 'Rakoto',
            'nights' => 3, 'stayed_on' => $depart->copy()->subDays(3), 'points' => ['photos'], 'flagged' => [],
            'confirmed_at' => $depart,
        ]);

        return $booking;
    }

    /** Le montant vient de la facture, jamais d'une saisie. */
    public function test_le_reglement_consigne_le_montant_de_la_facture(): void
    {
        $booking = $this->sejourDuMoisDernier();
        $owner = $booking->listing->owner;
        $mois = Carbon::today()->subMonthNoOverflow()->format('Y-m');

        $this->connecte()->post($this->office('/facturation/regler'), [
            'owner_id' => $owner->id, 'mois' => $mois, 'reference' => 'MP240915', 'amount' => 1,
        ])->assertSessionHas('succes');

        $reglement = InvoiceSettlement::query()->where('owner_id', $owner->id)->firstOrFail();
        $this->assertGreaterThanOrEqual(15000, $reglement->amount);
        $this->assertSame('MP240915', $reglement->reference);

        $this->connecte()->get($this->office('/facturation?mois='.$mois))
            ->assertInertia(fn ($page) => $page->where('totaux.reste', fn ($reste) => $reste >= 0));
    }

    /**
     * Consigné, le règlement **se retrouve** — et s'annule. La première
     * version castait le mois en date : SQLite le rangeait avec une heure, la
     * recherche par « AAAA-MM-01 » ne le trouvait plus, et la facture restait
     * « à régler » juste après avoir été réglée.
     */
    public function test_un_reglement_consigne_se_retrouve_et_s_annule(): void
    {
        $owner = $this->sejourDuMoisDernier()->listing->owner;
        $mois = Carbon::today()->subMonthNoOverflow()->format('Y-m');

        $this->connecte()->post($this->office('/facturation/regler'), ['owner_id' => $owner->id, 'mois' => $mois]);

        $this->connecte()->get($this->office('/facturation?mois='.$mois))
            ->assertInertia(fn ($page) => $page->where('lignes', fn ($lignes) => collect($lignes)->firstWhere('ownerId', $owner->id)['settlement']['amount'] > 0));

        $this->connecte()->post($this->office('/facturation/rouvrir'), ['owner_id' => $owner->id, 'mois' => $mois])
            ->assertSessionHas('succes');
        $this->assertSame(0, InvoiceSettlement::query()->count());
    }

    public function test_le_mois_en_cours_ne_se_regle_pas(): void
    {
        $owner = Owner::query()->firstOrFail();

        $this->connecte()->post($this->office('/facturation/regler'), [
            'owner_id' => $owner->id, 'mois' => Carbon::today()->format('Y-m'),
        ])->assertSessionHas('erreur');

        $this->assertSame(0, InvoiceSettlement::query()->count());
    }

    // ── L'équipe ────────────────────────────────────────────────────────

    public function test_on_ne_se_retire_pas_soi_meme_ni_le_dernier_membre(): void
    {
        $moi = $this->admin();

        $this->connecte()->post($this->office("/equipe/{$moi->id}/retirer"))->assertSessionHas('erreur');
        $this->assertTrue($moi->exists());

        $collegue = $this->admin('collegue@vayla.test', 'Collègue');
        $this->connecte()->post($this->office("/equipe/{$collegue->id}/retirer"))->assertSessionHas('succes');
        $this->assertNull(Admin::find($collegue->id));

        // Son geste reste au journal, sous son nom.
        $this->assertTrue(AdminAction::query()->where('kind', AdminActionKind::AdminRemoved->value)->exists());
    }

    /**
     * **Le mot de passe provisoire s'affiche une fois**, à celui qui ajoute le
     * membre ; aucun e-mail ne part. Le nouveau membre entre avec, puis en
     * choisit un.
     */
    public function test_ajouter_un_membre_montre_son_mot_de_passe_provisoire_une_fois(): void
    {
        Mail::fake();

        $reponse = $this->connecte()->post($this->office('/equipe'), ['name' => 'Tiana Rabe', 'email' => 'tiana@vayla.test'])
            ->assertSessionHas('provisoire');

        $provisoire = session('provisoire')['password'];
        Mail::assertNothingSent();

        $membre = Admin::query()->where('email', 'tiana@vayla.test')->firstOrFail();
        $this->assertFalse($membre->motDePasseChoisi());
        $this->assertTrue(Hash::check($provisoire, $membre->password));

        // Il s'affiche sur l'écran où l'on revient… puis plus jamais : un
        // rechargement l'efface.
        $this->connecte()->get($this->office('/equipe'))->assertInertia(fn ($page) => $page->where('provisoire.password', $provisoire));
        $this->connecte()->get($this->office('/equipe'))->assertInertia(fn ($page) => $page->where('provisoire', null));
    }

    public function test_un_collegue_recoit_un_nouveau_mot_de_passe_provisoire(): void
    {
        $collegue = $this->admin('collegue@vayla.test', 'Collègue');

        $this->connecte()->post($this->office("/equipe/{$collegue->id}/mot-de-passe"))->assertSessionHas('provisoire');

        $collegue->refresh();
        $this->assertFalse($collegue->motDePasseChoisi());
        $this->assertFalse(Hash::check(self::MOT_DE_PASSE, $collegue->password));
        $this->assertTrue(AdminAction::query()->where('kind', AdminActionKind::AdminPasswordReset->value)->exists());

        // Le sien se change par « Mon compte », jamais par cette porte.
        $moi = $this->admin();
        $this->connecte()->post($this->office("/equipe/{$moi->id}/mot-de-passe"))->assertSessionHas('erreur');
    }

    public function test_une_adresse_ne_rejoint_pas_l_equipe_deux_fois(): void
    {
        $this->connecte()->post($this->office('/equipe'), ['name' => 'Double', 'email' => 'EQUIPE@vayla.test'])
            ->assertSessionHasErrors('email');
    }

    public function test_la_commande_cree_le_premier_membre_avec_un_mot_de_passe_provisoire(): void
    {
        $this->artisan('vayla:admin', ['email' => 'Premier@Vayla.test', '--nom' => 'Premier Membre'])->assertSuccessful();

        $premier = Admin::query()->where('email', 'premier@vayla.test')->firstOrFail();
        $this->assertNotNull($premier->password);
        $this->assertFalse($premier->motDePasseChoisi());
    }

    /** Les comptes de test n'existent qu'en local : un mot de passe imprimé n'a rien à faire ailleurs. */
    public function test_les_comptes_de_test_sont_refuses_hors_du_local(): void
    {
        $this->artisan('vayla:comptes-test')->assertFailed();

        $this->assertFalse(Admin::query()->where('email', 'equipe@demo.vayla.test')->exists());
    }
}
