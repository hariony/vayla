<?php

namespace App\Providers;

use App\Contracts\Ai\AiChat;
use App\Contracts\Bookings\BookingCancellation;
use App\Contracts\Bookings\BookingThread;
use App\Contracts\Destinations\DestinationGallery;
use App\Contracts\Invoices\InvoiceCalculator;
use App\Contracts\Listings\ListingDrafting;
use App\Contracts\Listings\ListingGallery;
use App\Contracts\Office\ActionJournal;
use App\Contracts\Office\AdminPasswords;
use App\Contracts\Office\JournalReader;
use App\Contracts\Owners\OwnerAccess;
use App\Contracts\Photos\PhotoProcessor;
use App\Contracts\Photos\PhotoStorage;
use App\Contracts\Repositories\AdminActionRepositoryInterface;
use App\Contracts\Repositories\AdminRepositoryInterface;
use App\Contracts\Repositories\AmenityRepositoryInterface;
use App\Contracts\Repositories\BookingMessageRepositoryInterface;
use App\Contracts\Repositories\CategoryRepositoryInterface;
use App\Contracts\Repositories\DestinationGalleryRepositoryInterface;
use App\Contracts\Repositories\DestinationRepositoryInterface;
use App\Contracts\Repositories\InvoiceSettlementRepositoryInterface;
use App\Contracts\Repositories\ListingRepositoryInterface;
use App\Contracts\Repositories\OfficeAmenityRepositoryInterface;
use App\Contracts\Repositories\OfficeBookingRepositoryInterface;
use App\Contracts\Repositories\OfficeCategoryRepositoryInterface;
use App\Contracts\Repositories\OfficeDestinationRepositoryInterface;
use App\Contracts\Repositories\OfficeListingContentRepositoryInterface;
use App\Contracts\Repositories\OfficeListingRepositoryInterface;
use App\Contracts\Repositories\OfficeOwnerRepositoryInterface;
use App\Contracts\Repositories\OfficeStatsRepositoryInterface;
use App\Contracts\Repositories\OfficeTravellerRepositoryInterface;
use App\Contracts\Repositories\OfficeWhatsAppRepositoryInterface;
use App\Contracts\Repositories\OutboundMessageRepositoryInterface;
use App\Contracts\Repositories\OwnerRepositoryInterface;
use App\Contracts\Repositories\PageRepositoryInterface;
use App\Contracts\Repositories\PhotoLibraryRepositoryInterface;
use App\Contracts\Repositories\PhotoRepositoryInterface;
use App\Contracts\Repositories\SettingRepositoryInterface;
use App\Contracts\Repositories\SiteTextRepositoryInterface;
use App\Contracts\Repositories\StayRequestRepositoryInterface;
use App\Contracts\Repositories\UnavailabilityRepositoryInterface;
use App\Contracts\Settings\SettingsStore;
use App\Repositories\AdminActionRepository;
use App\Repositories\AdminRepository;
use App\Repositories\AmenityRepository;
use App\Repositories\BookingMessageRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\DestinationGalleryRepository;
use App\Repositories\DestinationRepository;
use App\Repositories\InvoiceSettlementRepository;
use App\Repositories\ListingRepository;
use App\Repositories\OfficeAmenityRepository;
use App\Repositories\OfficeBookingRepository;
use App\Repositories\OfficeCategoryRepository;
use App\Repositories\OfficeDestinationRepository;
use App\Repositories\OfficeListingContentRepository;
use App\Repositories\OfficeListingRepository;
use App\Repositories\OfficeOwnerRepository;
use App\Repositories\OfficeStatsRepository;
use App\Repositories\OfficeTravellerRepository;
use App\Repositories\OfficeWhatsAppRepository;
use App\Repositories\OutboundMessageRepository;
use App\Repositories\OwnerRepository;
use App\Repositories\PageRepository;
use App\Repositories\PhotoLibraryRepository;
use App\Repositories\PhotoRepository;
use App\Repositories\SettingRepository;
use App\Repositories\SiteTextRepository;
use App\Repositories\StayRequestRepository;
use App\Repositories\UnavailabilityRepository;
use App\Services\Ai\AiService;
use App\Services\BookingService;
use App\Services\ConversationService;
use App\Services\Currency\ExchangeRateProvider;
use App\Services\Currency\SettingExchangeRate;
use App\Services\Destinations\DestinationGalleryService;
use App\Services\InvoiceService;
use App\Services\Office\AdminJournal;
use App\Services\Office\Auth\AdminPasswordService;
use App\Services\OwnerListingService;
use App\Services\Owners\OwnerAccessLink;
use App\Services\Photos\GdPhotoProcessor;
use App\Services\Photos\LocalPhotoStorage;
use App\Services\PhotoUploadService;
use App\Services\Settings\SettingsService;
use App\Services\Verification\LogCodeSender;
use App\Services\Verification\MailCodeSender;
use App\Services\Verification\SmsCodeSender;
use App\Services\Verification\VerificationCodeService;
use App\Services\Verification\WhatsAppCodeSender;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Apple\Provider as AppleProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Inversion de dépendance : les services ne connaissent que les contrats.
     * Changer d'implémentation (cache, source externe, faux dépôt de test)
     * se fait ici, sans toucher une ligne de métier.
     */
    public function register(): void
    {
        $this->app->bind(PhotoRepositoryInterface::class, PhotoRepository::class);
        $this->app->bind(CategoryRepositoryInterface::class, CategoryRepository::class);
        $this->app->bind(DestinationRepositoryInterface::class, DestinationRepository::class);
        $this->app->bind(ListingRepositoryInterface::class, ListingRepository::class);
        $this->app->bind(AmenityRepositoryInterface::class, AmenityRepository::class);
        $this->app->bind(OwnerRepositoryInterface::class, OwnerRepository::class);
        $this->app->bind(UnavailabilityRepositoryInterface::class, UnavailabilityRepository::class);
        $this->app->bind(BookingMessageRepositoryInterface::class, BookingMessageRepository::class);
        $this->app->bind(OutboundMessageRepositoryInterface::class, OutboundMessageRepository::class);
        $this->app->bind(PhotoLibraryRepositoryInterface::class, PhotoLibraryRepository::class);
        $this->app->bind(DestinationGalleryRepositoryInterface::class, DestinationGalleryRepository::class);
        $this->app->bind(StayRequestRepositoryInterface::class, StayRequestRepository::class);

        // Les photos : le traitement d'image (GD aujourd'hui) et l'endroit où
        // vivent les fichiers (le disque public) sont des détails remplaçables.
        $this->app->bind(PhotoProcessor::class, GdPhotoProcessor::class);
        $this->app->bind(PhotoStorage::class, LocalPhotoStorage::class);
        $this->app->bind(DestinationGallery::class, DestinationGalleryService::class);
        $this->app->bind(ActionJournal::class, AdminJournal::class);
        $this->app->bind(JournalReader::class, AdminJournal::class);

        // Le contenu du back-office (lot 2a).
        $this->app->bind(AdminActionRepositoryInterface::class, AdminActionRepository::class);
        $this->app->bind(AdminRepositoryInterface::class, AdminRepository::class);
        $this->app->bind(SettingRepositoryInterface::class, SettingRepository::class);
        $this->app->bind(OfficeCategoryRepositoryInterface::class, OfficeCategoryRepository::class);
        $this->app->bind(OfficeAmenityRepositoryInterface::class, OfficeAmenityRepository::class);
        $this->app->bind(OfficeDestinationRepositoryInterface::class, OfficeDestinationRepository::class);
        $this->app->bind(OfficeListingContentRepositoryInterface::class, OfficeListingContentRepository::class);
        // Frontière du lot 3 : l'espace propriétaire rédige et range ses annonces
        // par les mêmes services, que le back-office ne connaît que par leur contrat.
        $this->app->bind(ListingDrafting::class, OwnerListingService::class);
        $this->app->bind(ListingGallery::class, PhotoUploadService::class);

        // Les lectures et les gestes du back-office (lot 2b), un repository par domaine.
        $this->app->bind(OfficeListingRepositoryInterface::class, OfficeListingRepository::class);
        $this->app->bind(OfficeBookingRepositoryInterface::class, OfficeBookingRepository::class);
        $this->app->bind(OfficeOwnerRepositoryInterface::class, OfficeOwnerRepository::class);
        $this->app->bind(OfficeTravellerRepositoryInterface::class, OfficeTravellerRepository::class);
        $this->app->bind(OfficeWhatsAppRepositoryInterface::class, OfficeWhatsAppRepository::class);
        $this->app->bind(OfficeStatsRepositoryInterface::class, OfficeStatsRepository::class);
        $this->app->bind(PageRepositoryInterface::class, PageRepository::class);
        $this->app->bind(SiteTextRepositoryInterface::class, SiteTextRepository::class);
        $this->app->bind(InvoiceSettlementRepositoryInterface::class, InvoiceSettlementRepository::class);
        // Frontières du lot 3 : factures, annulation, fil, accès du propriétaire.
        $this->app->bind(InvoiceCalculator::class, InvoiceService::class);
        $this->app->bind(BookingCancellation::class, BookingService::class);
        $this->app->bind(BookingThread::class, ConversationService::class);
        $this->app->bind(OwnerAccess::class, OwnerAccessLink::class);
        $this->app->bind(AdminPasswords::class, AdminPasswordService::class);
        $this->app->bind(AiChat::class, AiService::class);

        // Le taux de change vient de la configuration. Le remplacer par une
        // API de change se fait ici, et nulle part ailleurs.
        $this->app->bind(ExchangeRateProvider::class, SettingExchangeRate::class);
        $this->app->scoped(SettingsService::class);
        // Le contrat et la classe partagent la même instance : une seule lecture
        // de la table par requête, quel que soit le nom par lequel on la demande.
        $this->app->scoped(SettingsStore::class, fn ($app) => $app->make(SettingsService::class));

        /*
         * Les canaux du code à usage unique, dans l'ordre où on les
         * interroge. L'e-mail sert l'inscription — c'est le seul qui part
         * automatiquement sans entreprise enregistrée ni carte bancaire.
         *
         * Pour le téléphone, `log` reste le défaut : envoyer pour de vrai doit
         * être un choix explicite, jamais le résultat d'un oubli de
         * configuration — sinon la mise au point d'un écran part sur de vrais
         * téléphones, et se facture.
         */
        $this->app->bind(VerificationCodeService::class, fn () => new VerificationCodeService([
            new MailCodeSender,
            match (config('vayla.otp.driver')) {
                'whatsapp' => new WhatsAppCodeSender,
                'sms' => new SmsCodeSender,
                default => new LogCodeSender,
            },
        ]));
    }

    public function boot(): void
    {
        /*
         * **Apple n'a pas de pilote dans Socialite**, contrairement à Google et
         * Facebook : son flux diffère (le « secret » est un JWT signé, le nom
         * n'arrive qu'une fois) et Laravel laisse ça à un paquet communautaire.
         * L'enregistrer ici plutôt que dans un `EventServiceProvider` dédié :
         * le projet n'en a pas, et en créer un pour une ligne serait une couche
         * de plus pour rien.
         */
        Event::listen(function (SocialiteWasCalled $event) {
            $event->extendSocialite('apple', AppleProvider::class);
        });

        //
    }
}
