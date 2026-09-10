<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Listing;
use App\Models\Owner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Les deux écrans dont l'adresse **est** le droit d'accès.
 *
 * C'est un choix assumé — les propriétaires sont joints par WhatsApp,
 * beaucoup n'ont jamais créé de compte, et une référence de réservation se
 * dicte au téléphone — mais il déplace toute la sécurité sur l'URL. Deux
 * conséquences que ces tests tiennent :
 *
 * 1. **La limite de débit remplace la longueur du code.** La clé propriétaire
 *    fait trente-deux caractères : personne ne la devine. La référence de
 *    réservation en fait cinq, soit une trentaine de millions de
 *    combinaisons — et ce nombre rétrécit à chaque réservation créée. Sans
 *    limite, énumérer jusqu'à afficher le nom, le téléphone et les dates d'un
 *    inconnu est une affaire d'heures.
 * 2. **Ces pages ne s'indexent pas et ne fuitent pas leur adresse.** Il suffit
 *    qu'un lien soit collé une fois dans un espace public pour qu'une clé
 *    devienne trouvable par recherche.
 */
class KeyAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        RateLimiter::clear('');
    }

    private function reservation(): Booking
    {
        $listing = Listing::query()->where('slug', 'villa-ambatoloaka')->firstOrFail();

        return Booking::create([
            'reference' => 'VY-K3X9M',
            'listing_id' => $listing->id,
            'traveller' => 'Rakoto Jean',
            'traveller_phone' => '+261340000099',
            'guests' => 2,
            'arrival' => Carbon::today()->addDays(220)->toDateString(),
            'departure' => Carbon::today()->addDays(224)->toDateString(),
            'nights' => 4,
            'price_per_night' => 100000,
            'total' => 400000,
            'commission_rate' => 0.05,
            'status' => BookingStatus::Accepted,
        ]);
    }

    /**
     * **Le test qui compte.** Une référence courte se devine à force d'essais ;
     * ce qui l'empêche n'est pas sa longueur, c'est la limite de débit. Sans
     * elle, la page qui affiche le nom et le téléphone d'un voyageur est
     * ouverte à l'énumération.
     */
    public function test_l_enumeration_des_references_est_limitee(): void
    {
        $bloque = false;

        for ($i = 0; $i < 40; $i++) {
            if ($this->get('/reservations/VY-'.str_pad((string) $i, 5, 'Z', STR_PAD_LEFT))->status() === 429) {
                $bloque = true;
                break;
            }
        }

        $this->assertTrue($bloque, 'Les références se laissent énumérer sans limite de débit.');
    }

    public function test_l_enumeration_des_liens_d_acces_est_limitee(): void
    {
        $bloque = false;

        for ($i = 0; $i < 40; $i++) {
            if ($this->get('/proprietaire/acces/'.str_pad((string) $i, 32, 'z', STR_PAD_LEFT))->status() === 429) {
                $bloque = true;
                break;
            }
        }

        $this->assertTrue($bloque, 'Les liens d’accès se laissent énumérer sans limite de débit.');
    }

    /** Un formulaire de connexion sans limite est un formulaire d'essai de mots de passe. */
    /**
     * **Il n'y a plus de mot de passe à essayer, mais il reste des codes.**
     *
     * Six chiffres se devinent à force d'essais, et derrière il y a l'espace
     * d'un propriétaire — ses réservations, ses voyageurs. La limite de débit
     * est ce qui tient la porte, pas la longueur du code. Le service en pose
     * une seconde, par code (cinq essais), mais celle-ci protège en amont :
     * elle vaut même contre quelqu'un qui n'a jamais reçu de code.
     */
    public function test_l_essai_de_codes_est_limite(): void
    {
        $bloque = false;

        for ($i = 0; $i < 40; $i++) {
            $reponse = $this->post('/proprietaire/inscription/code', [
                'code' => str_pad((string) $i, 6, '0', STR_PAD_LEFT),
            ]);

            if ($reponse->status() === 429) {
                $bloque = true;
                break;
            }
        }

        $this->assertTrue($bloque, 'Les codes se laissent essayer sans limite.');
    }

    /**
     * Une balise `<meta>` supposerait que le robot exécute le JavaScript
     * d'Inertia. L'en-tête, lui, part avec la réponse quoi qu'il arrive.
     */
    public function test_l_espace_proprietaire_ne_s_indexe_pas(): void
    {
        $owner = Owner::query()->whereNotNull('access_key')->firstOrFail();

        $this->actingAs($owner, 'proprietaire')
            ->get('/proprietaire')
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive')
            // Sans ça, un clic vers un site tiers emporterait l'URL complète,
            // clé comprise, dans les journaux de ce site.
            ->assertHeader('Referrer-Policy', 'no-referrer');
    }

    public function test_le_suivi_de_reservation_ne_s_indexe_pas(): void
    {
        $this->reservation();

        $this->get('/reservations/VY-K3X9M')
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive')
            ->assertHeader('Referrer-Policy', 'no-referrer');
    }

    /** Le catalogue, lui, doit s'indexer : c'est là que les voyageurs arrivent. */
    public function test_le_catalogue_reste_indexable(): void
    {
        $this->get('/logements')->assertOk()->assertHeaderMissing('X-Robots-Tag');
        $this->get('/')->assertOk()->assertHeaderMissing('X-Robots-Tag');
    }
}
