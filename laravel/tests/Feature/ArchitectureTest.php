<?php

namespace Tests\Feature;

use App\Contracts\Ai\AiChat;
use App\Contracts\Destinations\DestinationGallery;
use App\Contracts\Office\ActionJournal;
use App\Contracts\Office\AdminPasswords;
use App\Contracts\Photos\PhotoProcessor;
use App\Contracts\Photos\PhotoStorage;
use App\Contracts\Repositories\DestinationGalleryRepositoryInterface;
use App\Contracts\Repositories\OfficeStatsRepositoryInterface;
use App\Contracts\Repositories\PageRepositoryInterface;
use App\Contracts\Repositories\PhotoLibraryRepositoryInterface;
use App\Contracts\Repositories\SiteTextRepositoryInterface;
use App\Contracts\Repositories\StayRequestRepositoryInterface;
use App\Repositories\DestinationGalleryRepository;
use App\Repositories\OfficeStatsRepository;
use App\Repositories\PageRepository;
use App\Repositories\PhotoLibraryRepository;
use App\Repositories\SiteTextRepository;
use App\Repositories\StayRequestRepository;
use App\Services\Ai\AiService;
use App\Services\Destinations\DestinationGalleryService;
use App\Services\Office\AdminJournal;
use App\Services\Office\Auth\AdminPasswordService;
use App\Services\Photos\GdPhotoProcessor;
use App\Services\Photos\LocalPhotoStorage;
use Tests\TestCase;

/**
 * Les règles d'architecture, **tenues par des tests plutôt que par la
 * mémoire** : sans eux, la prochaine urgence remettrait une requête dans un
 * contrôleur.
 *
 * Elles couvrent le code déjà mis en conformité — la photothèque et les
 * demandes de séjour (lot 1). Chaque lot suivant ajoute ses fichiers aux
 * listes ; le jour où tout y est, les listes deviennent des dossiers.
 */
class ArchitectureTest extends TestCase
{
    /** Les contrôleurs mis en conformité. */
    private const CONTROLEURS = [
        'Http/Controllers/Office/PhotoLibraryController.php',
        'Http/Controllers/Office/StayRequestController.php',
        'Http/Controllers/StayRequestController.php',
        // Lot 2a — le contenu du back-office.
        'Http/Controllers/Office/ContentListingController.php',
        'Http/Controllers/Office/DestinationController.php',
        'Http/Controllers/Office/CategoryController.php',
        'Http/Controllers/Office/AmenityController.php',
        'Http/Controllers/Office/SettingsController.php',
        // Lot 2b — lectures et gestes du back-office.
        'Http/Controllers/Office/DashboardController.php',
        'Http/Controllers/Office/ListingController.php',
        'Http/Controllers/Office/BookingController.php',
        'Http/Controllers/Office/OwnerController.php',
        'Http/Controllers/Office/TravellerController.php',
        'Http/Controllers/Office/WhatsAppController.php',
        'Http/Controllers/Office/InvoiceController.php',
        'Http/Controllers/Office/JournalController.php',
        'Http/Controllers/Office/TeamController.php',
        // Lot 2c — la porte et le compte.
        'Http/Controllers/Office/AuthController.php',
        'Http/Controllers/Office/AccountController.php',
        // Lot 2d — les statistiques.
        'Http/Controllers/Office/StatsController.php',
        // Lot 2e — les textes du site, l'IA.
        'Http/Controllers/Office/PageController.php',
        'Http/Controllers/Office/SiteTextController.php',
        'Http/Controllers/PageController.php',
        'Http/Controllers/AiController.php',
        'Http/Controllers/Office/OfficeController.php',
        'Http/Controllers/Office/NotFoundController.php',
    ];

    /** Les middlewares mis en conformité : mêmes règles qu'un contrôleur. */
    private const MIDDLEWARES = [
        'Http/Middleware/OfficeContext.php',
    ];

    /** Les services mis en conformité : ils ne parlent qu'aux contrats. */
    private const SERVICES = [
        'Services/Photos/PhotoLibraryQuery.php',
        'Services/Photos/TeamPhotoUploader.php',
        'Services/Photos/PhotoCreditEditor.php',
        'Services/Photos/PhotoRemover.php',
        'Services/Photos/PhotoRemovalPolicy.php',
        'Services/StayRequests/StayRequestSubmitter.php',
        'Services/StayRequests/StayRequestFormQuery.php',
        'Services/StayRequests/StayRequestQueueQuery.php',
        'Services/StayRequests/StayRequestWorkflow.php',
        'Services/Destinations/DestinationGalleryService.php',
        // Lot 2a — le contenu du back-office.
        'Services/Office/Content/ListingContentQuery.php',
        'Services/Office/Content/ListingContentEditor.php',
        'Services/Office/Content/ListingPhotoEditor.php',
        'Services/Office/Content/DestinationQuery.php',
        'Services/Office/Content/DestinationEditor.php',
        'Services/Office/Content/DestinationGalleryEditor.php',
        'Services/Office/Content/CategoryQuery.php',
        'Services/Office/Content/CategoryEditor.php',
        'Services/Office/Content/AmenityQuery.php',
        'Services/Office/Content/AmenityEditor.php',
        'Services/Office/Content/SettingsQuery.php',
        'Services/Office/Content/SettingsEditor.php',
        'Services/Office/AdminJournal.php',
        'Services/Settings/SettingsService.php',
        'Services/Support/UniqueSlug.php',
        'Services/Support/PositionSwapper.php',
        // Lot 2b — lectures et gestes du back-office.
        'Services/Office/Dashboard/DashboardQuery.php',
        'Services/Office/Listings/ListingQueueQuery.php',
        'Services/Office/Listings/ListingModerationQuery.php',
        'Services/Office/ModerationService.php',
        'Services/Office/Bookings/BookingQueueQuery.php',
        'Services/Office/Bookings/BookingDetailQuery.php',
        'Services/Office/Bookings/BookingInterventions.php',
        'Services/Office/Owners/OwnerQueueQuery.php',
        'Services/Office/Owners/OwnerDetailQuery.php',
        'Services/Office/Owners/OwnerSupport.php',
        'Services/Office/Travellers/TravellerQuery.php',
        'Services/Office/WhatsApp/WhatsAppQueueQuery.php',
        'Services/Office/WhatsApp/WhatsAppDispatch.php',
        'Services/Office/Invoices/InvoiceLines.php',
        'Services/Office/Invoices/InvoicesQuery.php',
        'Services/Office/Invoices/InvoiceSettlements.php',
        'Services/Office/Journal/JournalQuery.php',
        'Services/Office/Team/TeamQuery.php',
        'Services/Office/Team/TeamMembers.php',
        // Lot 2c — la porte et le compte.
        'Services/Office/Auth/OfficeLogin.php',
        'Services/Office/Auth/AdminPasswordService.php',
        // Lot 2d — les statistiques.
        'Services/Office/Stats/StatsQuery.php',
        'Services/Office/Stats/RequestStats.php',
        'Services/Office/Stats/ActivityStats.php',
        'Services/Office/Stats/CatalogueStats.php',
        'Services/Office/Stats/MonthGrid.php',
        'Services/Office/Stats/CommissionStats.php',
        // Lot 2e — les textes du site, l'IA, les compteurs de la colonne.
        'Services/Content/Pages/PageRenderer.php',
        'Services/Content/Pages/PageAddresses.php',
        'Services/Content/Pages/SitePages.php',
        'Services/Content/Pages/PageQuery.php',
        'Services/Content/Pages/PageEditor.php',
        'Services/Content/Texts/SiteTexts.php',
        'Services/Content/Texts/SiteTextEditor.php',
        'Services/Content/Texts/SiteTextQuery.php',
        'Services/Office/OfficeCountersQuery.php',
        'Services/Ai/AiService.php',
    ];

    public function test_chaque_contrat_se_resout_vers_son_implementation(): void
    {
        foreach ([
            PhotoLibraryRepositoryInterface::class => PhotoLibraryRepository::class,
            DestinationGalleryRepositoryInterface::class => DestinationGalleryRepository::class,
            StayRequestRepositoryInterface::class => StayRequestRepository::class,
            PhotoProcessor::class => GdPhotoProcessor::class,
            PhotoStorage::class => LocalPhotoStorage::class,
            DestinationGallery::class => DestinationGalleryService::class,
            ActionJournal::class => AdminJournal::class,
            AdminPasswords::class => AdminPasswordService::class,
            AiChat::class => AiService::class,
            OfficeStatsRepositoryInterface::class => OfficeStatsRepository::class,
            PageRepositoryInterface::class => PageRepository::class,
            SiteTextRepositoryInterface::class => SiteTextRepository::class,
        ] as $contrat => $implementation) {
            $this->assertInstanceOf($implementation, app($contrat), $contrat);
        }
    }

    /** Recevoir, appeler un service, répondre : ni requête, ni validation en ligne. */
    public function test_les_controleurs_ne_font_ni_requete_ni_validation(): void
    {
        foreach ([...self::CONTROLEURS, ...self::MIDDLEWARES] as $fichier) {
            $code = $this->code($fichier);

            $this->assertDoesNotMatchRegularExpression('/[A-Z]\w+::(query|where|find|create|firstOrCreate|updateOrCreate)\(/', $code, "{$fichier} interroge un modèle.");
            $this->assertStringNotContainsString('DB::', $code, $fichier);
            $this->assertStringNotContainsString('->validate(', $code, "{$fichier} valide en ligne : c'est le rôle d'un FormRequest.");
            $this->assertStringNotContainsString('Validator::make', $code, $fichier);
        }
    }

    /** Les services passent par les repositories — et ne reçoivent jamais la Request. */
    public function test_les_services_ne_parlent_qu_aux_contrats(): void
    {
        foreach (self::SERVICES as $fichier) {
            $code = $this->code($fichier);

            $this->assertDoesNotMatchRegularExpression('/[A-Z]\w+::(query|where|find|create)\(/', $code, "{$fichier} interroge Eloquent directement.");
            $this->assertDoesNotMatchRegularExpression('/DB::(table|select|raw)/', $code, "{$fichier} écrit du SQL : c'est le rôle d'un repository.");
            $this->assertDoesNotMatchRegularExpression('/->(save|forceFill)\(/', $code, "{$fichier} écrit un modèle : c'est le rôle d'un repository.");
            $this->assertStringNotContainsString('Illuminate\\Http\\Request', $code, "{$fichier} reçoit la Request : il doit recevoir un DTO.");
        }
    }

    /** Le code, sans les commentaires : un test qui échoue parce qu'on a documenté la règle n'apprend rien. */
    private function code(string $fichier): string
    {
        $tokens = token_get_all((string) file_get_contents(app_path($fichier)));

        return implode('', array_map(
            fn ($t) => is_array($t) ? (in_array($t[0], [T_COMMENT, T_DOC_COMMENT], true) ? '' : $t[1]) : $t,
            $tokens,
        ));
    }
}
