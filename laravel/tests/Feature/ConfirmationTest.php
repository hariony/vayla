<?php

namespace Tests\Feature;

use App\Enums\TrustLevel;
use App\Models\Listing;
use App\Models\StayConfirmation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Les confirmations de séjour remplacent la note sur cinq. Ce qui doit donc
 * être verrouillé, c'est l'absence de note — et la présence des « non ».
 */
class ConfirmationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function fiche(string $slug): array
    {
        return $this->getJson("/api/v1/listings/{$slug}")->assertOk()->json('data');
    }

    public function test_aucune_note_nest_publiee(): void
    {
        $data = $this->fiche('villa-ambatoloaka');

        // Le contrôle porte sur le bloc des confirmations, pas sur toute la
        // charge utile : `note` existe déjà, légitimement, comme précision
        // d'une rubrique d'équipements. C'est la **notation** qui est
        // proscrite, pas le mot.
        $json = json_encode([$data['confirmed'], $data['confirmations']]);

        // Une moyenne finit toujours par remplacer les faits qu'elle résume.
        foreach (['rating', 'score', 'stars', 'average', 'moyenne', 'etoile'] as $interdit) {
            $this->assertStringNotContainsString(
                "\"{$interdit}\"",
                $json,
                "Les confirmations publient une clé de notation « {$interdit} »."
            );
        }

        // Et rien qui ressemble à une note dans les valeurs non plus.
        foreach ($data['confirmed']['points'] as $point) {
            $this->assertSame(
                ['key', 'label', 'long', 'icon', 'confirmed', 'flagged', 'answered'],
                array_keys($point)
            );
        }
    }

    public function test_les_signalements_sortent_avec_les_confirmations(): void
    {
        $data = $this->fiche('villa-ambatoloaka');

        $equipements = collect($data['confirmed']['points'])->firstWhere('key', 'amenities');

        $this->assertSame(2, $equipements['confirmed']);
        $this->assertSame(1, $equipements['flagged']);
        $this->assertSame(3, $equipements['answered']);

        // Le témoignage porte le détail en clair, pas seulement un compteur.
        $signale = collect($data['confirmations'])->firstWhere('traveller', 'Claire');
        $this->assertContains('amenities', $signale['flagged']);
        $this->assertStringContainsString('climatisation', $signale['mismatch']);
    }

    public function test_un_point_sans_reponse_na_pas_de_barre(): void
    {
        $listing = Listing::where('slug', 'lodge-andasibe')->firstOrFail();
        $listing->confirmations()->delete();

        StayConfirmation::create([
            'listing_id' => $listing->id,
            'traveller' => 'Test',
            'nights' => 2,
            'stayed_on' => now()->subDays(10)->toDateString(),
            'points' => ['photos'],
            'flagged' => [],
            'is_demo' => true,
            'confirmed_at' => now()->subDays(8),
        ]);

        $points = $this->fiche('lodge-andasibe')['confirmed']['points'];

        // Une barre à zéro se lit comme un échec, alors que personne ne s'est
        // prononcé : elle ne doit pas exister.
        $this->assertCount(1, $points);
        $this->assertSame('photos', $points[0]['key']);
    }

    public function test_seules_les_annonces_de_niveau_quatre_portent_des_confirmations(): void
    {
        // Le niveau 4 se DÉFINIT par « des voyageurs y ont dormi et ont
        // confirmé » : une annonce de niveau 2 avec des confirmations serait
        // une contradiction dans les données.
        foreach (Listing::with('confirmations')->get() as $listing) {
            if ($listing->confirmations->isEmpty()) {
                continue;
            }

            $this->assertSame(
                TrustLevel::Proven,
                $listing->trust_level,
                "L'annonce « {$listing->slug} » porte des confirmations sans être au niveau 4."
            );
        }
    }

    public function test_le_jeu_de_demonstration_contient_au_moins_un_signalement(): void
    {
        // Un jeu où tout le monde confirme tout ne prouverait pas que le bloc
        // sait afficher un « non » — c'est pourtant ce qui le distingue d'une
        // moyenne étoilée.
        $this->assertTrue(
            StayConfirmation::query()->whereNotNull('mismatch')->exists(),
            'Aucune confirmation de démonstration ne signale de problème.'
        );
    }

    public function test_hors_mode_demo_aucune_confirmation_fictive_nest_servie(): void
    {
        config(['vayla.demo' => false]);

        // Toutes les confirmations semées sont fictives : hors démo, le bloc
        // doit tomber sur son état vide, pas afficher des témoignages.
        $listing = Listing::where('slug', 'villa-ambatoloaka')->firstOrFail();
        $listing->update(['is_demo' => false]);

        $data = $this->fiche('villa-ambatoloaka');

        $this->assertSame(0, $data['confirmed']['stays']);
        $this->assertEmpty($data['confirmations']);
    }

    public function test_seul_le_prenom_du_voyageur_est_publie(): void
    {
        foreach ($this->fiche('villa-ambatoloaka')['confirmations'] as $c) {
            // Un voyageur confirme un fait, il ne signe pas une tribune :
            // rien ne doit permettre de le retrouver.
            $this->assertArrayNotHasKey('email', $c);
            $this->assertArrayNotHasKey('phone', $c);
            $this->assertStringNotContainsString(' ', trim($c['traveller']));
        }
    }
}
