<?php

namespace Tests\Feature;

use App\Contracts\Currency\ExchangeRateProvider;
use App\Data\DeviseData;
use App\Services\Currency\ConfigExchangeRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * L'euro affiché à côté de l'ariary.
 *
 * Ce que ces tests protègent n'est pas un calcul — diviser par cinq mille ne
 * se casse pas — mais **une couture et une règle** :
 *
 * 1. **La couture.** Le taux vient d'une interface, pas d'un `config()` semé
 *    dans les vues. Le jour où une API de change le fournit, seule la liaison
 *    d'`AppServiceProvider` change. Un test remplace l'implémentation pour le
 *    prouver : si ça marche ici, ça marchera avec l'API.
 * 2. **La règle.** L'euro est une aide à la lecture pour le voyageur ; le
 *    propriétaire, lui, est réglé en ariary par mobile money. Aucun montant
 *    en euros ne doit apparaître dans son espace, et surtout pas sur sa
 *    facture — ce serait un chiffre de plus à rapprocher, pour rien.
 */
class ExchangeRateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_le_taux_est_partage_avec_sa_date(): void
    {
        config(['vayla.currency.eur_rate' => 5000, 'vayla.currency.eur_rate_date' => '2026-09-04']);

        $this->get('/logements/villa-ambatoloaka')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('devise.code', 'EUR')
                ->where('devise.taux', 5000)
                // La date est **saisie** avec le taux, jamais déduite de
                // `now()` : « taux du jour » sur une valeur figée il y a six
                // mois serait exactement le petit mensonge qu'on refuse.
                ->where('devise.releveLe', '2026-09-04'));
    }

    /**
     * Le point de la couture : on remplace la source du taux, rien d'autre.
     * Aucune vue, aucun contrôleur, aucun composant n'a à savoir d'où il vient.
     */
    public function test_changer_de_source_de_taux_ne_touche_qu_une_liaison(): void
    {
        $this->app->bind(ExchangeRateProvider::class, fn () => new class implements ExchangeRateProvider
        {
            public function ariaryParEuro(): float
            {
                return 4875.5;
            }

            public function releveLe(): string
            {
                return '2027-01-15';
            }
        });

        $this->get('/logements/villa-ambatoloaka')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('devise.taux', 4875.5)
                ->where('devise.releveLe', '2027-01-15'));
    }

    public function test_l_api_publie_le_meme_taux_que_le_site(): void
    {
        $this->getJson('/api/v1/exchange-rate')
            ->assertOk()
            ->assertJsonPath('data.code', 'EUR')
            ->assertJsonPath('data.symbole', '€')
            ->assertJsonPath('data.taux', 5000);
    }

    /**
     * **Aucun prix converti côté serveur.** Les montants restent des entiers
     * d'ariary de bout en bout : convertir dans les props rendrait impossible
     * l'euro d'un total qui n'existe que côté client — les nuits choisies
     * multipliées par le tarif, dans l'encart de réservation.
     */
    public function test_les_prix_restent_en_ariary_entiers(): void
    {
        $this->get('/logements')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where(
                'listings',
                fn ($annonces) => collect($annonces)->every(fn ($a) => is_int($a['price']))
            ));
    }

    /**
     * **Aucun euro sur ce que le propriétaire reçoit ou doit.**
     *
     * Il est réglé en ariary par mobile money : un euro à côté d'un total de
     * séjour, d'une commission ou d'une ligne de facture serait un chiffre de
     * plus à rapprocher, pour rien.
     *
     * **Une exception, et elle est le contraire d'une entorse : le champ
     * "tarif" du formulaire d'annonce.** Ce montant-là n'est pas de l'argent
     * qu'il touche, c'est le prix que des voyageurs européens vont lire sur sa
     * fiche — en euros, puisque c'est ce que le site affiche. Le lui cacher
     * pendant qu'il le fixe reviendrait à lui interdire de voir son annonce
     * comme la voit la moitié de ses clients.
     *
     * Le contrôle est **statique**, sur les fichiers : le taux est partagé
     * globalement — l'exclure par route coupleraient le middleware aux URL —
     * donc le symbole figure de toute façon dans les props de chaque page. Ce
     * qui doit rester vrai, c'est qu'aucun écran d'argent ne s'en serve.
     */
    public function test_aucun_montant_recu_par_le_proprietaire_n_est_en_euros(): void
    {
        $ecrans = [
            'Partials/RequestList.vue',   // ce qu'il reçoit, et la commission
            'Partials/UpcomingList.vue',  // les séjours à venir
            'Partials/InvoiceCard.vue',   // la facture du mois
            'Partials/ListingList.vue',   // ses tarifs, en lecture
            'Bookings/Index.vue',         // l'historique et ses commissions
            'Listings/Index.vue',         // ses annonces, en lecture
        ];

        foreach ($ecrans as $ecran) {
            $chemin = resource_path("js/Pages/Owner/{$ecran}");

            $this->assertFileExists($chemin, "Écran d'argent introuvable : {$ecran}");

            $source = file_get_contents($chemin);

            $this->assertStringNotContainsString('useDevise', $source,
                "Le propriétaire est réglé en ariary : {$ecran}");
            $this->assertStringNotContainsString('€', $source,
                "Le propriétaire est réglé en ariary : {$ecran}");
        }
    }

    public function test_un_taux_absent_n_affiche_pas_de_conversion(): void
    {
        // Le repli du fournisseur : zéro veut dire « pas de conversion
        // affichable », jamais une division par zéro ni un montant à
        // l'infini. Le front saute simplement la ligne.
        config(['vayla.currency.eur_rate' => 0]);

        $this->assertSame(0.0, (new ConfigExchangeRate)->ariaryParEuro());
        $this->assertSame(0.0, DeviseData::depuis(new ConfigExchangeRate)->taux);
    }
}
